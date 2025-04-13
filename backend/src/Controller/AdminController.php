<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Repository\PostRepository;
use App\Repository\ForumCategoryRepository;
use App\Repository\CharacterRepository;
use App\Repository\LocationRepository;
use App\Repository\CharacterRelationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        ForumRepository $forumRepository,
        ThreadRepository $threadRepository,
        PostRepository $postRepository,
        ForumCategoryRepository $categoryRepository,
        CharacterRepository $characterRepository,
        LocationRepository $locationRepository,
        CharacterRelationRepository $relationRepository
    ): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'userCount' => $userRepository->count([]),
            'forumCount' => $forumRepository->count([]),
            'threadCount' => $threadRepository->count([]),
            'postCount' => $postRepository->count([]),
            'categoryCount' => $categoryRepository->count([]),
            'characterCount' => $characterRepository->count([]),
            'locationCount' => $locationRepository->count([]),
            'relationCount' => $relationRepository->count([]),
            'latestUsers' => $userRepository->findBy([], ['createdAt' => 'DESC'], 5),
            'latestThreads' => $threadRepository->findBy([], ['createdAt' => 'DESC'], 5),
            'latestPosts' => $postRepository->findBy([], ['createdAt' => 'DESC'], 5),
            'latestCharacters' => $characterRepository->findBy([], ['createdAt' => 'DESC'], 4),
        ]);
    }
}