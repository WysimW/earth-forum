<?php

// src/Controller/ThreadController.php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Forum;
use App\Entity\Thread;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ThreadController extends AbstractController
{
    #[Route('/api/threads/{id}', name: 'get_thread_detail', methods: ['GET'])]
    public function getThreadDetail(Thread $thread): JsonResponse
    {
        $posts = [];

        foreach ($thread->getPosts() as $post) {
            $posts[] = [
                'postId' => $post->getId(),
                'author' => $post->getAuthor()->getPseudo(),
                'avatar' => $post->getAuthor()->getAvatar(),
                'date' => $post->getCreatedAt()->format('Y-m-d'),
                'content' => $post->getContent(),
            ];
        }

        $breadcrumb = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => $thread->getForum()->getName(), 'url' => '/forum/' . $thread->getForum()->getId()],
            ['name' => $thread->getTitle(), 'url' => '/thread/' . $thread->getId()]
        ];

        $data = [
            'threadId' => $thread->getId(),
            'title' => $thread->getTitle(),
            'author' => $thread->getAuthor()->getPseudo(),
            'date' => $thread->getCreatedAt()->format('Y-m-d'),
            'breadcrumb' => $breadcrumb,
            'posts' => $posts,
        ];

        return new JsonResponse($data);
    }

    #[Route('/api/threads', name: 'create_thread', methods: ['POST'])]
    public function createThread(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        $forum = $em->getRepository(Forum::class)->find($data['forum_id']);
        if (!$forum) {
            return new JsonResponse(['status' => 'Forum not found'], JsonResponse::HTTP_NOT_FOUND);
        }
    
        $author = $em->getRepository(User::class)->find($data['author_id']);
        if (!$author) {
            return new JsonResponse(['status' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }
    
        $thread = new Thread();
        $thread->setTitle($data['title']);
        $thread->setForum($forum);
        $thread->setAuthor($author);
        $thread->setCreatedAt(new \DateTimeImmutable());

    
        $post = new Post();
        $post->setContent($data['content']);
        $post->setThread($thread);
        $post->setAuthor($author);
        $post->setCreatedAt(new \DateTimeImmutable());

    
        $em->persist($thread);
        $em->persist($post);
        $em->flush();
    
        return new JsonResponse(['status' => 'Thread created'], JsonResponse::HTTP_CREATED);
    }
    
}
