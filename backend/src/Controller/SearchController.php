<?php

namespace App\Controller;

use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Service\BreadcrumbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    private BreadcrumbService $breadcrumbService;

    public function __construct(BreadcrumbService $breadcrumbService)
    {
        $this->breadcrumbService = $breadcrumbService;
    }

    #[Route('/search', name: 'app_roleplay_search')]
    public function search(Request $request, ThreadRepository $threadRepository, ForumRepository $forumRepository): Response
    {
        $keyword = $request->query->get('keyword', '');
        $status = $request->query->get('status', '');
        $forumType = $request->query->get('forum_type', '');
        
        // Recherche de threads en fonction des critères
        $threads = $threadRepository->searchThreads($keyword, $status, $forumType);
        
        // Recherche de forums en fonction du type
        $forums = [];
        if ($forumType) {
            $isRoleplay = $forumType === 'roleplay';
            $forums = $forumRepository->findBy(['isRoleplay' => $isRoleplay]);
        }
        
        return $this->render('search/index.html.twig', [
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

    #[Route('/search/advanced', name: 'app_roleplay_search_advanced')]
    public function advancedSearch(Request $request, ThreadRepository $threadRepository, ForumRepository $forumRepository): Response
    {
        // Cette méthode pourra être implémentée plus tard pour une recherche avancée
        // avec plus de critères (date, auteur, personnage, etc.)
        return $this->redirectToRoute('app_roleplay_search');
    }
}