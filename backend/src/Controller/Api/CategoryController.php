<?php

// src/Controller/CategoryController.php

namespace App\Controller\Api;

use App\Repository\ForumRepository;
use App\Repository\ForumCategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CategoryController extends AbstractController
{
    private $forumRepository;

    public function __construct(ForumRepository $forumRepository)
    {
        $this->forumRepository = $forumRepository;
    }

    #[Route('/api/categories', name: 'get_categories', methods: ['GET'])]
    public function getCategories(ForumCategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAllWithForums();

        $data = [];

        foreach ($categories as $category) {
            $forums = [];

            foreach ($category->getForums() as $forum) {
                $subForums = [];
                $id = $forum->getID();
                $latestThread = $this->forumRepository->findLatestThreadByRecentPostInForum($id);

                if ($latestThread != null) {
                    $lastPost = $this->getEntityManager()->getRepository(\App\Entity\Post::class)
                        ->findLastPostForThread($latestThread->getId());
                    
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
                ];
            }

            $data[] = [
                'id' => $category->getId(),
                'categoryName' => $category->getName(),
                'description' => $category->getDescription(),
                'categoryType' => $category->getType()->getName(),
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
