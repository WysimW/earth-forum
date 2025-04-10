<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Service\BreadcrumbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ForumController extends AbstractController
{
    private BreadcrumbService $breadcrumbService;

    public function __construct(BreadcrumbService $breadcrumbService)
    {
        $this->breadcrumbService = $breadcrumbService;
    }

    #[Route('/forum/{id}', name: 'app_forum_show')]
    public function show(Forum $forum, ForumRepository $forumRepository, ThreadRepository $threadRepository): Response
    {
        $subForums = $forumRepository->findBy(['parent' => $forum], ['position' => 'ASC']);
        $threads = $threadRepository->findBy(['forum' => $forum], ['sticky' => 'DESC', 'updatedAt' => 'DESC']);
        
        return $this->render('forum/show.html.twig', [
            'forum' => $forum,
            'subForums' => $subForums,
            'threads' => $threads,
            'breadcrumbs' => $this->getBreadcrumbsForForum($forum),
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