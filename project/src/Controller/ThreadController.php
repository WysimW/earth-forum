<?php

// src/Controller/ThreadController.php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Forum;
use App\Entity\Thread;
use App\Repository\UserRepository;
use App\Service\BreadcrumbService;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ThreadController extends AbstractController
{   private $breadcrumbService;

    public function __construct(BreadcrumbService $breadcrumbService)
    {
        $this->breadcrumbService = $breadcrumbService;
    }
    
    #[Route('/api/threads/{id}', name: 'get_thread_detail', methods: ['GET'])]
    public function getThreadDetail(Thread $thread): JsonResponse
    {
        $posts = [];


        $breadcrumbs = $this->breadcrumbService->generateBreadcrumbsForThread($thread);


        foreach ($thread->getPosts() as $post) {
            $posts[] = [
                'postId' => $post->getId(),
                'author' => $post->getAuthor()->getPseudo(),
                'avatar' => $post->getAuthor()->getAvatar(),
                'date' => $post->getCreatedAt()->format('Y-m-d'),
                'content' => $post->getContent(),
            ];
        }

        $data = [
            'threadId' => $thread->getId(),
            'title' => $thread->getTitle(),
            'author' => $thread->getAuthor()->getPseudo(),
            'date' => $thread->getCreatedAt()->format('Y-m-d'),
            'breadcrumb' => $breadcrumbs,
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
    

    #[Route('/api/threads/{id}/posts', name: 'create_post', methods: ['POST'])]
    public function createPost(Request $request, ThreadRepository $threadRepository, UserRepository $userRepository, EntityManagerInterface $em, int $id): JsonResponse
    {
        // Fetch the thread by its ID
        $thread = $threadRepository->find($id);
    
        // If the thread does not exist, return a 404 error
        if (!$thread) {
            return new JsonResponse(['error' => 'Thread not found'], 404);
        }
    
        // Simulate fetching the logged-in user (for demo purposes, set to user ID 1)
        $authorId = 1; // This would be replaced by actual user authentication logic
        $author = $userRepository->find($authorId);
    
        // If the user is not found, return a 404 error
        if (!$author) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }
    
        // Get the post data from the request
        $data = json_decode($request->getContent(), true);
    
        // Validate the request data (optional but recommended)
        if (!isset($data['content']) || empty(trim($data['content']))) {
            return new JsonResponse(['error' => 'Content is required'], 400);
        }
    
        // Create a new Post entity
        $post = new Post();
        $post->setContent($data['content']);
        $post->setThread($thread);
        $post->setAuthor($author); // Set the author of the post
    
        // Set timestamps for the post
        $post->setCreatedAt(new \DateTimeImmutable());
    
        // Persist the new post in the database
        $em->persist($post);
        $em->flush();
    
        // Return a success response
        return new JsonResponse(['message' => 'Post created successfully', 'postId' => $post->getId()], 201);
    }
    
    #[Route('/api/threads/{id}/breadcrumb', name: 'get_thread_breadcrumb', methods: ['GET'])]
    public function getThreadBreadcrumb(int $id, ThreadRepository $threadRepository): JsonResponse
    {
        // Récupérer le thread par ID
        $thread = $threadRepository->find($id);

        if (!$thread) {
            return new JsonResponse(['error' => 'Thread not found'], 404);
        }

        // Utiliser le service BreadcrumbService pour générer les breadcrumbs du thread
        $breadcrumbs = $this->breadcrumbService->generateBreadcrumbsForThread($thread);

        return new JsonResponse($breadcrumbs);
    }
}
