<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\Univers;
use App\Repository\ForumRepository;
use App\Repository\PostRepository;
use App\Repository\ThreadRepository;
use App\Repository\UniversRepository;
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

    #[Route('/univers/{universeSlug}/forum/{id}', name: 'app_forum_show')]
    public function show(string $universeSlug, Forum $forum, Request $request, UniversRepository $universRepository, ForumRepository $forumRepository, ThreadRepository $threadRepository, PostRepository $postRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Vérifier que le forum appartient bien à cet univers
        $forumUniverse = $forum->getUniverse();
        if ($forumUniverse && $forumUniverse->getId() !== $univers->getId()) {
            throw $this->createNotFoundException('Ce forum n\'appartient pas à cet univers');
        }

        // Si c'est un forum d'elseworld, vérifier que l'elseworld appartient à l'univers
        $elseworld = $forum->getElseworld();
        if ($elseworld && $elseworld->getParentUniverse()->getId() !== $univers->getId()) {
            throw $this->createNotFoundException('Ce forum appartient à un elseworld qui n\'est pas lié à cet univers');
        }
        
        // Récupérer les sous-forums triés par type (important > roleplay > hrp) puis par position
        $subForums = $forumRepository->createQueryBuilder('f')
            ->where('f.parent = :parent')
            ->setParameter('parent', $forum)
            ->orderBy('CASE f.type 
                WHEN \'important\' THEN 1 
                WHEN \'roleplay\' THEN 2 
                WHEN \'hrp\' THEN 3 
                ELSE 4 END', 'ASC')
            ->addOrderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();
        
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
            'univers' => $univers,
            'forum' => $forum,
            'subForums' => $subForums,
            'threads' => $threads,
            'breadcrumbs' => $this->getBreadcrumbsForForum($univers, $forum),
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalThreads
            ],
        ]);
    }

    private function getBreadcrumbsForForum(Univers $univers, Forum $forum, array $additional = []): array
    {
        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_univers_index'),
            $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $univers->getSlug()])
        ];
        
        $currentForum = $forum;
        $parentForums = [];
        
        while ($parent = $currentForum->getParent()) {
            $parentForums[] = $parent;
            $currentForum = $parent;
        }
        
        $parentForums = array_reverse($parentForums);
        
        // Si le forum appartient à un elseworld, l'ajouter dans le breadcrumb
        if ($forum->getElseworld()) {
            $elseworld = $forum->getElseworld();
            $breadcrumbs['Elseworlds'] = $this->generateUrl('app_univers_elseworlds', ['slug' => $univers->getSlug()]);
            $breadcrumbs[$elseworld->getName()] = $this->generateUrl('app_elseworld_show', [
                'universeSlug' => $univers->getSlug(),
                'elseworldSlug' => $elseworld->getSlug()
            ]);
        } else {
            $breadcrumbs['Forums'] = $this->generateUrl('app_univers_forums', ['slug' => $univers->getSlug()]);
        }
        
        foreach ($parentForums as $parentForum) {
            $breadcrumbs[$parentForum->getName()] = $this->generateUrl('app_forum_show', [
                'universeSlug' => $univers->getSlug(),
                'id' => $parentForum->getId()
            ]);
        }
        
        $breadcrumbs[$forum->getName()] = $this->generateUrl('app_forum_show', [
            'universeSlug' => $univers->getSlug(),
            'id' => $forum->getId()
        ]);
        
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->breadcrumbService->generate($breadcrumbs);
    }
}