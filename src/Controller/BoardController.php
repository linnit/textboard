<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\Post;
use App\Form\BoardType;
use App\Form\PostType;
use App\Repository\BoardRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class BoardController extends AbstractController
{
    /**
     * @Route("/", name="board_index", methods={"GET"})
     */
    public function index(BoardRepository $boardRepository): Response
    {
        return $this->render('board/index.html.twig', [
            'boards' => $boardRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="board_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $board = new Board();
        $form = $this->createForm(BoardType::class, $board);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $board->setPassword($passwordEncoder->encodePassword(
                $board,
                $board->getPassword()
            ));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($board);
            $entityManager->flush();

            return $this->redirectToRoute('board_index');
        }

        return $this->render('board/new.html.twig', [
            'board' => $board,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{name}/{page_no<\d+>?1}", name="board_show", methods={"GET", "POST"})
     */
    public function show(Board $board, int $page_no, PostRepository $postRepository, Request $request): Response
    {
        $post = new Post();
        $post->setBoard($board);
        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);

        if ($form->isSubmitted() &&
            $form->isValid()) {
            $response = $this->forward('App\Controller\PostController::postFormSubmitted', [
                'post' => $post
            ]);

            return $response;
        }

        $criteria = ['parent_post' => null, 'board' => $board];

        $repPosts = $postRepository->findBy(
            $criteria,
            ["latestpost" => "DESC"],
            12,
            ($page_no-1) * 12
        );

        $pageCount = ceil($postRepository->getPageCount($criteria) / 12);

        return $this->render('board/show.html.twig', [
            'board' => $board,
            'posts' => $repPosts,
            'page_count' => $pageCount,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{name}/post/{id}/{newPostId?}",
     * name="post_show", methods={"GET", "POST"},
     * requirements={"id"="\d+",
     * "newPostId"="\d+"})
     */
    public function showPost(Post $post, int $newPostId = null)
    {
        $response = $this->forward('App\Controller\PostController::show', [
            'post'  => $post,
            'newPostId' => $newPostId
        ]);

        return $response;
    }

    /**
     * @Route("/boardadmin/{name}/edit", name="board_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Board $board, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $this->denyAccessUnlessGranted('BOARD_EDIT', $board);

        $form = $this->createForm(BoardType::class, $board);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $board->setPassword($passwordEncoder->encodePassword(
                $board,
                $board->getPassword()
            ));

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('board_index');
        }

        return $this->render('board/edit.html.twig', [
            'board' => $board,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/boardadmin/{name}/denied", name="board_denied", methods={"GET"})
     */
    public function denied(Board $board): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if ($board === $user) {
            return $this->redirectToRoute('board_edit', ['name' => $board->getName()]);
        }

        return $this->render('board/denied.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/{name}", name="board_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Board $board): Response
    {
        if ($this->isCsrfTokenValid('delete'.$board->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($board);
            $entityManager->flush();
        }

        return $this->redirectToRoute('board_index');
    }
}
