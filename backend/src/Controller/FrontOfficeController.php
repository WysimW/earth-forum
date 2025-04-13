<?php

namespace App\Controller;

use App\Repository\CharacterRepository;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Service\BreadcrumbService;
use App\Service\LastPostService;
use App\Service\ForumStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/')]
class FrontOfficeController extends AbstractController
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

    #[Route('/', name: 'app_roleplay')]
    public function index(ForumRepository $forumRepository): Response
    {
        // Récupérer les forums principaux (sans parent)
        $mainForums = $forumRepository->findBy(['parent' => null], ['position' => 'ASC']);
        
        // Enrichir les forums avec les informations sur les derniers posts et les statistiques
        foreach ($mainForums as $forum) {
            $lastPostInfo = $this->lastPostService->getLastPostInfoForForum($forum->getId());
            $forum->lastPostInfo = $lastPostInfo;
            
            // Ajouter les statistiques cumulées
            $stats = $this->forumStatsService->getForumStats($forum->getId());
            $forum->stats = $stats;
        }
        
        return $this->render('forum/index.html.twig', [
            'mainForums' => $mainForums,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
            ]),
        ]);
    }
    
    #[Route('/dashboard', name: 'app_roleplay_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(
        CharacterRepository $characterRepository, 
        ThreadRepository $threadRepository
    ): Response
    {
        $user = $this->getUser();
        
        // Récupérer les personnages de l'utilisateur
        $characters = $characterRepository->findBy(['user' => $user]);
        
        // Récupérer les threads créés par l'utilisateur (RP et HRP)
        $createdThreads = $threadRepository->findBy([
            'author' => $user
        ], ['updatedAt' => 'DESC'], 5);
        
        // Récupérer les threads RP où l'utilisateur participe avec ses personnages
        $participatingThreads = $threadRepository->findRecentThreadsWithUserParticipation($user->getId(), 5);
        
        // Récupérer les threads récemment actifs
        $recentThreads = $threadRepository->findRecentActiveThreads(5);
        
        return $this->render('forum/dashboard.html.twig', [
            'characters' => $characters,
            'createdThreads' => $createdThreads,
            'participatingThreads' => $participatingThreads,
            'recentThreads' => $recentThreads,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Tableau de bord' => $this->generateUrl('app_roleplay_dashboard'),
            ]),
        ]);
    }
    
    #[Route('/search', name: 'app_roleplay_search')]
    public function search(Request $request, ThreadRepository $threadRepository, ForumRepository $forumRepository): Response
    {
        $keyword = $request->query->get('keyword', '');
        $status = $request->query->get('status', '');
        $forumType = $request->query->get('forum_type', ''); // RP ou HRP
        
        // Recherche de threads en fonction des critères
        $threads = $threadRepository->searchThreads($keyword, $status, $forumType);
        
        // Recherche de forums en fonction du type
        $forums = [];
        if ($forumType) {
            $isRoleplay = $forumType === 'roleplay';
            $forums = $forumRepository->findBy(['isRoleplay' => $isRoleplay]);
        }
        
        return $this->render('search.html.twig', [
            'threads' => $threads,
            'forums' => $forums,
            'keyword' => $keyword,
            'status' => $status,
            'forumType' => $forumType,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Recherche' => $this->generateUrl('app_roleplay_search', $request->query->all()),
            ]),
        ]);
    }
}
