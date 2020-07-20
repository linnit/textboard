<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Enqueue\Client\ProducerInterface;
use Liip\ImagineBundle\Async\Commands;
use Liip\ImagineBundle\Async\ResolveCache;

/**
 * @Route("/post")
 */
class PostController extends AbstractController
{
    /**
     * @Route("/{id}/{newPostId?}", methods={"GET", "POST"}, requirements={"id"="\d+", "newPostId"="\d+"})
     */
    public function show(Post $post, int $newPostId = null, Request $request, ProducerInterface $producer): Response
    {
        $newChildPost = new Post();
        $newChildPost->setBoard($post->getBoard());
        $newChildPost->setParentPost($post);

        $form = $this->createForm(PostType::class, $newChildPost);

        $form->handleRequest($request);

        if ($form->isSubmitted() &&
            $form->isValid()) {
            return $this->postFormSubmitted($newChildPost, $producer);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post->getRootParentPost(),
            'new_post_id' => $newPostId,
            'form' => $form->createView(),
        ]);
    }

    // [TODO] Tidy
    public function postFormSubmitted(Post $post, ProducerInterface $producer)
    {
        $post->setCreated(new DateTime());
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $post->setIpAddress($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            $post->setIpAddress($_SERVER['REMOTE_ADDR']);
        }

        // We will lose access to Post::imageFile so need to save the mimetype
        if ($post->getImageFile()) {
            $post->setImageMimeType($post->getImageFile()->getMimeType());
        }

        $rootPost = $post->getRootParentPost();
        $boardName = $post->getBoard()->getName();

        // Update parent post timestamp
        $rootPost->setLatestpost(new DateTime);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($rootPost);
        $entityManager->persist($post);
        $entityManager->flush();

        // Resolve cached images in the background (thumbnails, stripping exif)
        if (preg_match('/^image\//', $post->getImageMimeType())) {
            $reply = $producer->sendCommand(
                Commands::RESOLVE_CACHE,
                new ResolveCache($post->getImageName(), ['thumb', 'jpeg']),
                true
            );

            $replyMessage = $reply->receive(20000); // wait for 20 sec
        }

        $newPostId = $post->getId();

        return $this->redirectToRoute('post_show', [
            'name' => $boardName,
            'id' => $rootPost->getId(),
            // Only show newPostId if it's a child post
            'newPostId' => $rootPost->getId() == $newPostId ? null : $newPostId
        ]);
    }

    /**
     * @Route("/{id}", name="post_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Post $post, CacheManager $liipCacheManager): Response
    {
        $boardIndex = $post->getBoard()->getName();
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            // Remove LiipImagine image cache
            $liipCacheManager->remove($post->getImageName());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($post);
            $entityManager->flush();
        }

        $boardName = $post->getBoard()->getName();

        if ($post->getParentPost()) {
            return $this->redirectToRoute('post_show', [
                'name' => $boardName,
                'id' => $post->getRootParentPost()->getId()
                ]);
        } else {
            return $this->redirectToRoute('board_show', ['name' => $boardIndex]);
        }
    }
}
