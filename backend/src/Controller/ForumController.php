<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Repository\ForumRepository;
use App\Repository\PostRepository;
use App\Repository\ThreadRepository;
use App\Service\BreadcrumbService;
use App\Service\LastPostService;
use App\Service\ForumStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ForumController extends AbstractController
{
    private BreadcrumbService $breadcrumbService;
    private LastPostService $lastPostService;
    private ForumStatisticsService $forumStatsService;

    public function __construct(
        BreadcrumbService $breadcrumbService, 
        LastPostService $lastPostService,
        ForumStatisticsService $forumStatsService
    ) {
        $this->breadcrumbService = $breadcrumbService;
        $this->lastPostService = $lastPostService;
        $this->forumStatsService = $forumStatsService;
    }

    #[Route('/forum/{id}', name: 'app_forum_show')]
    public function show(Forum $forum, Request $request, ForumRepository $forumRepository, ThreadRepository $threadRepository, PostRepository $postRepository): Response
    {
        // Récupérer les sous-forums
        $subForums = $forumRepository->findBy(['parent' => $forum], ['position' => 'ASC']);
        
        // Enrichir les sous-forums avec les informations sur le dernier post et les statistiques
        foreach ($subForums as $subforum) {
            $lastPostInfo = $this->lastPostService->getLastPostInfoForForum($subforum->getId());
            $subforum->lastPostInfo = $lastPostInfo;
            
            // Ajouter les statistiques cumulées
            $stats = $this->forumStatsService->getForumStats($subforum->getId());
            $subforum->stats = $stats;
        }
        
        // Pagination des threads
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Récupérer les threads avec pagination
        $totalThreads = $threadRepository->count(['forum' => $forum]);
        $totalPages = ceil($totalThreads / $limit);
        $threads = $threadRepository->findBy(
            ['forum' => $forum], 
            ['sticky' => 'DESC', 'updatedAt' => 'DESC'],
            $limit,
            $offset
        );
        
        // Enrichir les threads avec les derniers posts
        foreach ($threads as $thread) {
            $lastPost = $postRepository->findLastPostForThread($thread->getId());
            $thread->lastPost = $lastPost;
            
            // Si c'est un thread RP, récupérer les participants
            if ($thread->getType() === 'roleplay') {
                $participants = $postRepository->findDistinctCharactersByThread($thread->getId());
                // Use public attribute for temporary data rather than accessing private property
                $thread->characterParticipants = $participants;
            }
        }
        
        // Récupération des informations du dernier post pour le forum courant
        $forum->lastPostInfo = $this->lastPostService->getLastPostInfoForForum($forum->getId());
        
        // Ajouter les statistiques cumulées du forum courant
        $forum->stats = $this->forumStatsService->getForumStats($forum->getId());
        
        return $this->render('forum/show.html.twig', [
            'forum' => $forum,
            'subForums' => $subForums,
            'threads' => $threads,
            'breadcrumbs' => $this->getBreadcrumbsForForum($forum),
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalThreads
            ],
        ]);
    }

    private function getBreadcrumbsForForum(Forum $forum, array $additional = []): array
    {
        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_roleplay')
        ];
        
        $currentForum = $forum;
        $parentForums = [];
        
        while ($parent = $currentForum->getParent()) {
            $parentForums[] = $parent;
            $currentForum = $parent;
        }
        
        $parentForums = array_reverse($parentForums);
        
        foreach ($parentForums as $parentForum) {
            $breadcrumbs[$parentForum->getName()] = $this->generateUrl('app_forum_show', ['id' => $parentForum->getId()]);
        }
        
        $breadcrumbs[$forum->getName()] = $this->generateUrl('app_forum_show', ['id' => $forum->getId()]);
        
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->breadcrumbService->generate($breadcrumbs);
    }
}