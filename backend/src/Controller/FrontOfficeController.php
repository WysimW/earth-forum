<?php

namespace App\Controller;

use App\Repository\CharacterRepository;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Repository\UniversRepository;
use App\Repository\PostRepository;
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
    #[Route('/', name: 'app_home')]
    public function index(UniversRepository $universRepository, ThreadRepository $threadRepository, PostRepository $postRepository): Response
    {
        // Récupérer tous les univers
        $univers = $universRepository->findAll();
        
        // Récupérer les 5 derniers threads actifs (RP)
        $recentThreads = $threadRepository->findRecentActiveThreads(5);
        
        // Récupérer les 5 derniers messages postés sur le forum
        $recentPosts = $postRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Statistiques globales
        $totalThreads = $threadRepository->count([]);
        $totalPosts = $postRepository->count([]);
        
        return $this->render('front_office/index.html.twig', [
            'univers' => $univers,
            'recentThreads' => $recentThreads,
            'recentPosts' => $recentPosts,
            'totalThreads' => $totalThreads,
            'totalPosts' => $totalPosts,
        ]);
    }
    
    #[Route('/dashboard', name: 'app_roleplay_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(
        CharacterRepository $characterRepository, 
        ThreadRepository $threadRepository
    ): Response
    {   
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Récupérer les personnages de l'utilisateur
        $characters = $characterRepository->findBy(['user' => $user]);
        
        // Récupérer les threads créés par l'utilisateur (RP et HRP)
        $createdThreads = $threadRepository->findBy([
            'author' => $user
        ], ['updatedAt' => 'DESC'], 5);
        
        // Récupérer les threads RP où l'utilisateur participe avec ses personnages
        $participatingThreads = [];
        if ($user) {
            // L'utilisateur doit être connecté à ce stade (IsGranted('ROLE_USER'))
            // et la classe User possède bien une méthode getId()
            $participatingThreads = $threadRepository->findRecentThreadsWithUserParticipation($user->getId(), 5);
        }
        
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
    public function search(Request $request, ThreadRepository $threadRepository, ForumRepository $forumRepository, UniversRepository $universRepository): Response
    {
        $keyword = $request->query->get('keyword', '');
        $status = $request->query->get('status', '');
        $forumType = $request->query->get('forum_type', ''); // RP ou HRP
        $universeId = $request->query->get('universe_id', null);
        
        // Récupérer tous les univers pour le filtre
        $allUniverses = $universRepository->findAll();
        
        // Recherche de threads en fonction des critères
        $threads = $threadRepository->searchThreads($keyword, $status, $forumType);
        
        // Si un univers est spécifié, filtrer les résultats
        if ($universeId) {
            $universe = $universRepository->find($universeId);
            if ($universe) {
                // Utiliser une requête plus spécifique si nécessaire
                // Pour l'instant, on filtre manuellement
                $filteredThreads = [];
                foreach ($threads as $thread) {
                    $forum = $thread->getForum();
                    $forumUniverse = $forum->getUniverse();
                    $elseworld = $forum->getElseworld();
                    
                    if (($forumUniverse && $forumUniverse->getId() == $universeId) || 
                        ($elseworld && $elseworld->getParentUniverse()->getId() == $universeId)) {
                        $filteredThreads[] = $thread;
                    }
                }
                $threads = $filteredThreads;
            }
        }
        
        // Recherche de forums en fonction du type
        $forums = [];
        if ($forumType) {
            $isRoleplay = $forumType === 'roleplay';
            $forums = $forumRepository->findBy(['isRoleplay' => $isRoleplay]);
            
            // Si un univers est spécifié, filtrer les forums
            if ($universeId) {
                $filteredForums = [];
                foreach ($forums as $forum) {
                    $forumUniverse = $forum->getUniverse();
                    $elseworld = $forum->getElseworld();
                    
                    if (($forumUniverse && $forumUniverse->getId() == $universeId) || 
                        ($elseworld && $elseworld->getParentUniverse()->getId() == $universeId)) {
                        $filteredForums[] = $forum;
                    }
                }
                $forums = $filteredForums;
            }
        }
        
        return $this->render('search.html.twig', [
            'threads' => $threads,
            'forums' => $forums,
            'keyword' => $keyword,
            'status' => $status,
            'forumType' => $forumType,
            'universeId' => $universeId,
            'universes' => $allUniverses,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Recherche' => $this->generateUrl('app_roleplay_search', $request->query->all()),
            ]),
        ]);
    }
}
