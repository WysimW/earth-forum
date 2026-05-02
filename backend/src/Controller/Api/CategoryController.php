<?php

// src/Controller/CategoryController.php

namespace App\Controller\Api;

use App\Repository\ForumRepository;
use App\Repository\ForumCategoryRepository;
use App\Repository\PostRepository;
use App\Repository\UniversRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CategoryController extends AbstractController
{
    private $forumRepository;
    private $postRepository;
    private $universRepository;

    public function __construct(ForumRepository $forumRepository, PostRepository $postRepository, UniversRepository $universRepository)
    {
        $this->forumRepository = $forumRepository;
        $this->postRepository = $postRepository;
        $this->universRepository = $universRepository;
    }

    #[Route('/api/categories', name: 'get_categories', methods: ['GET'])]
    public function getCategories(ForumCategoryRepository $categoryRepository, Request $request): JsonResponse
    {
        $universeSlug = $request->query->get('universe');
        $universeId = $request->query->get('universeId');
        
        // Récupérer l'univers si un filtre est fourni
        $univers = null;
        if ($universeSlug) {
            $univers = $this->universRepository->findOneBy(['slug' => $universeSlug]);
        } elseif ($universeId) {
            $univers = $this->universRepository->find($universeId);
        }
        
        $categories = $categoryRepository->findAllWithForums();

        $data = [];

        foreach ($categories as $category) {
            $forums = [];

            foreach ($category->getForums() as $forum) {
                // Filtrer par univers si spécifié
                if ($univers && $forum->getUniverse() !== $univers) {
                    continue;
                }
                $subForums = [];
                $id = $forum->getID();
                $latestThread = $this->forumRepository->findLatestThreadByRecentPostInForum($id);

                if ($latestThread != null) {
                    $lastPost = $this->postRepository->findLastPostForThread($latestThread->getId());
                    
                    if ($lastPost) {
                        $lastPostDate = $lastPost->getCreatedAt()->format('H\hi \l\e d/m/y');
                        $formattedDate = 'Posté à ' . $lastPostDate;
                        $lastThreadData = [
                            'id' => $latestThread->getId(),
                            'title' => $latestThread->getTitle(),
                            'author' => $latestThread->getAuthor()->getPseudo(),
                            'avatar' => $latestThread->getAuthor()->getAvatar(),
                            'date' => $formattedDate,
                        ];
                    } else {
                        $lastThreadData = [];
                    }
                } else {
                    $lastThreadData = [];
                }

                $stats = $this->forumRepository->countThreadsAndPostsInForum($id);
                $statsData = [
                    'totalThreads' => $stats['totalThreads'],
                    'totalPosts' => $stats['totalPosts'],
                ];

                foreach ($forum->getSubforums() as $subForum) {
                    $subForums[] = [
                        'id' => $subForum->getId(),
                        'name' => $subForum->getName(),
                        'description' => $subForum->getDescription(),
                        'bannerImage' => $subForum->getBanner(),
                    ];
                };

                $forums[] = [
                    'id' => $forum->getId(),
                    'name' => $forum->getName(),
                    'description' => $forum->getDescription(),
                    'banner' => $forum->getBanner(),
                    'heroLogo' => $forum->getHeroLogo(),
                    'lastThread' => $lastThreadData,
                    'stats' => $statsData,
                    'subforums' => $subForums,
                    'universeId' => $forum->getUniverse()?->getId(),
                    'universe' => $forum->getUniverse() ? [
                        'id' => $forum->getUniverse()->getId(),
                        'name' => $forum->getUniverse()->getName(),
                        'slug' => $forum->getUniverse()->getSlug(),
                    ] : null,
                ];
            }

            // Ne pas inclure les catégories vides si un filtre univers est appliqué
            if ($univers && empty($forums)) {
                continue;
            }

            $data[] = [
                'id' => $category->getId(),
                'categoryName' => $category->getName(),
                'description' => $category->getDescription(),
                'categoryType' => $category->getType()?->getName(),
                'forums' => $forums,
                'order' =>  $category->getHomeOrder(),
            ];
        }

        return new JsonResponse(['categories' => $data]);
    }

    #[Route('/api/categories/list', name: 'cat_get_categories', methods: ['GET'])]
    public function getCategoriesList(ForumCategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
        }

        return new JsonResponse($data);
    }
}
