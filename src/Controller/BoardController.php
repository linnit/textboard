<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\Post;
use App\Form\Board\NewBoardType;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Util\BoardUtil;
use App\Util\PostUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/")
 */
class BoardController extends AbstractController
{
    /**
     * @Route("/", name="board_index", methods={"GET"})
     */
    public function index(BoardUtil $boardUtil, PostRepository $postRepository): Response
    {
        $recentActive = $postRepository->findBy([], ['latestpost' => 'DESC'], 10);


        return $this->render('board/index.html.twig', [
            'boards' => $boardUtil->boards(),
            'postCount' => $boardUtil->boardPostCountAll(),
            'recent_active_threads' => $recentActive
        ]);
    }

    /**
     * @Route("/{name}/{page<page>?page}/{page_no<\d+>?1}", name="board_show", methods={"GET", "POST"}, priority=-1)
     */
    public function show(Board $board, int $page_no, Request $request, PostRepository $postRepository, PostUtil $postUtil): Response
    {
        $post = new Post();
        $post->setBoard($board);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postUtil->createPost($post, $request);

            return $this->redirectToRoute('post_show', ['board' => $board->getName(), 'post' => $post->getSlug()]);
        }

        return $this->render('board/show.html.twig', [
            'board' => $board,
            'paginator' => $postRepository->findLatest($page_no, $board),
            'form' => $form->createView(),
        ]);
    }
}
