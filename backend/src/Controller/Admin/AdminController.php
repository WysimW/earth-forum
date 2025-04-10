<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(
        UserRepository $userRepository,
        ForumRepository $forumRepository,
        ThreadRepository $threadRepository,
        PostRepository $postRepository
    ): Response 
    {
        // Récupération des statistiques
        $stats = [
            'users' => $userRepository->count([]),
            'forums' => $forumRepository->count([]),
            'threads' => $threadRepository->count([]),
            'posts' => $postRepository->count([]),
        ];

        // Récupération des derniers utilisateurs enregistrés
        $latestUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // Récupération des derniers threads créés
        $latestThreads = $threadRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'latestUsers' => $latestUsers,
            'latestThreads' => $latestThreads,
        ]);
    }
}