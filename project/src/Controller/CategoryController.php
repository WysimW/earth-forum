<?php

// src/Controller/CategoryController.php

namespace App\Controller;

use App\Repository\ForumCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{   
    #[Route('/api/categories', name: 'get_categories', methods: ['GET'])]
    public function getCategories(ForumCategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();

        $data = [];

        foreach ($categories as $category) {
            $forums = [];

            foreach ($category->getForums() as $forum) {
                $forums[] = [
                    'id' => $forum->getId(),
                    'name' => $forum->getName(),
                    'description' => $forum->getDescription(),
                    'banner' => $forum->getBanner(),
                    'lastThread' => $forum->getLastPostInfo(), // Method to retrieve last thread info
                ];
            }

            $data[] = [
                'id' => $category->getId(),
                'categoryName' => $category->getName(),
                'description' => $category->getDescription(),
                'forums' => $forums,
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
