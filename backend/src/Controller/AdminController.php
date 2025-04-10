<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Forum;
use App\Entity\Thread;
use App\Entity\ForumCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'dashboard')]
    public function dashboard(): Response
    {
        // Statistiques générales
        $userCount = $this->entityManager->getRepository(User::class)->count([]);
        $forumCount = $this->entityManager->getRepository(Forum::class)->count([]);
        $threadCount = $this->entityManager->getRepository(Thread::class)->count([]);
        $postCount = $this->entityManager->getRepository(Post::class)->count([]);
        $categoryCount = $this->entityManager->getRepository(ForumCategory::class)->count([]);

        // Derniers utilisateurs inscrits
        $latestUsers = $this->entityManager->getRepository(User::class)
            ->findBy([], ['createdAt' => 'DESC'], 5);

        // Derniers threads créés
        $latestThreads = $this->entityManager->getRepository(Thread::class)
            ->findBy([], ['createdAt' => 'DESC'], 5);

        // Derniers posts
        $latestPosts = $this->entityManager->getRepository(Post::class)
            ->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'userCount' => $userCount,
            'forumCount' => $forumCount,
            'threadCount' => $threadCount,
            'postCount' => $postCount,
            'categoryCount' => $categoryCount,
            'latestUsers' => $latestUsers,
            'latestThreads' => $latestThreads,
            'latestPosts' => $latestPosts,
        ]);
    }
}