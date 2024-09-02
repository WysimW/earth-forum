<?php

// src/Controller/ForumController.php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\ForumCategory;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ForumCategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\BreadcrumbService;


class ForumController extends AbstractController
{    private $entityManager;
    private $forumRepository;
    private $categoryRepository;
    private $breadcrumbService;

    public function __construct(EntityManagerInterface $entityManager,ForumRepository $forumRepository, ForumCategoryRepository $categoryRepository, BreadcrumbService $breadcrumbService) 
    {
        $this->entityManager = $entityManager;
        $this->forumRepository = $forumRepository;
        $this->categoryRepository = $categoryRepository;
        $this->breadcrumbService = $breadcrumbService;

    }

    #[Route('/api/forumslist', name: 'get_forum_listing', methods: ['GET'])]
    public function getForumList(ForumRepository $forumRepository): JsonResponse
    {
        $forums = $forumRepository->findAll();

        $data = [];

        foreach ($forums as $forum) {
            $data[] = [
                'id' => $forum->getId(),
                'name' => $forum->getName(),
                'description' => $forum->getDescription(),
                'banner' => $forum->getBanner(),
                'category_id' => $forum->getCategory() ? $forum->getCategory()->getId() : null,
                'parent_forum_id' => $forum->getForum() ? $forum->getForum()->getId() : null,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/forums', name: 'get_all_forums', methods: ['GET'])]
    public function getAllForums(ForumRepository $forumRepository): JsonResponse
    {
        $forums = $forumRepository->findAll();

        $data = [];

        foreach ($forums as $forum) {
            $data[] = [
                'id' => $forum->getId(),
                'name' => $forum->getName(),
                'description' => $forum->getDescription(),
                'banner' => $forum->getBanner(),
                
            ];
        }

        return new JsonResponse($data);
    }


#[Route('/api/forums/{id}', name: 'get_forum_read', methods: ['GET'])]
public function getForumDetail(Forum $forum): JsonResponse
{
    $subForums = [];

    foreach ($forum->getSubforums() as $subForum) {
        $id = $subForum->getID();
        $latestThread = $this->forumRepository->findLatestThreadByRecentPostInForum($id);
        if ($latestThread != null) {
            $lastPostDate = $latestThread->getPosts()->last()->getCreatedAt()->format('H\hi \l\e d/m/y');
            $formattedDate = 'Posté à ' . $lastPostDate;
            $lastThreadData = [
                'id' => $latestThread->getId(),
                'title' => $latestThread->getTitle(),
                'author' => $latestThread->getAuthor()->getPseudo(), // Assuming Thread entity has a relation to Author
                'avatar' => $latestThread->getAuthor()->getAvatar(),
                'date' => $formattedDate, // Get the date of the last post
            ];
        } else {
            $lastThreadData = [];
        };

        $subForums[] = [
            'id' => $subForum->getId(),
            'name' => $subForum->getName(),
            'description' => $subForum->getDescription(),
            'banner' => $subForum->getBanner(),
            'lastThread' => $lastThreadData, // Method to retrieve last thread info
        ];
    }

    $threads = [];

    foreach ($forum->getThreads() as $thread) {
        $lastPost = $thread->getLastPostInfo();
        $threads[] = [
            'threadId' => $thread->getId(),
            'title' => $thread->getTitle(),
            'author' => $thread->getAuthor()->getPseudo(),
            'date' => $thread->getCreatedAt()->format('Y-m-d'),
            'lastPost' => $lastPost ? [
                'id' => $lastPost['id'] ?? null,
                'author' => $lastPost['author'] ?? null,
                'avatar' => $lastPost['avatar'] ?? null,
                'date' => $lastPost['date'] ?? null,
                'excerpt' => $lastPost['excerpt'] ?? null,
            ] : null,
        ];
    };

    $isRoleplay = $this->forumRepository->isForumOrParentInCategoryType($forum, 'roleplay');
    $breadcrumbs = $this->breadcrumbService->generateBreadcrumbs($forum);

    $data = [
        'forumId' => $forum->getId(),
        'forumName' => $forum->getName(),
        'description' => $forum->getDescription(),
        'bannerImage' => $forum->getBanner(),
        'isRoleplay' => $isRoleplay,
        'breadcrumb' => $breadcrumbs,
        'subForums' => $subForums,
        'threads' => $threads,
    ];

    return new JsonResponse($data);
}


#[Route('/api/forums/{id}/edit-data', name: 'get_forum_edit_data', methods: ['GET'])]
public function getForumEditData(Forum $forum, ForumCategoryRepository $categoryRepository ): JsonResponse
{
    // Get the list of categories
    $categories = $this->categoryRepository->findAll();

    // Get the list of forums for parent selection
    $forums = $this->forumRepository->findAll();

    // Prepare the data
    $data = [
        'forum' => [
            'id' => $forum->getId(),
            'name' => $forum->getName(),
            'description' => $forum->getDescription(),
            'banner' => $forum->getBanner(),
            'category_id' => $forum->getCategory() ? $forum->getCategory()->getId() : null,
            'parent_forum_id' => $forum->getForum() ? $forum->getForum()->getId() : null,
        ],
        'categories' => array_map(function($category) {
            return ['id' => $category->getId(), 'name' => $category->getName()];
        }, $categories),
        'forums' => array_map(function($forum) {
            return ['id' => $forum->getId(), 'name' => $forum->getName()];
        }, $forums),
    ];

    return new JsonResponse($data);
}


    

    #[Route('/api/forums', name: 'create_forum', methods: ['POST'])]
    public function createForum(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!empty($data['parent_forum_id'])) {
            $parentForum = $this->entityManager->getRepository(Forum::class)->find($data['parent_forum_id']);
            if (!$parentForum) {
                return new JsonResponse(['status' => 'Parent forum not found'], JsonResponse::HTTP_NOT_FOUND);
            }
            $forum = new Forum();
            $forum->setForum($parentForum);
        } elseif (!empty($data['category_id'])) {
            $category = $this->entityManager->getRepository(ForumCategory::class)->find($data['category_id']);
            if (!$category) {
                return new JsonResponse(['status' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
            }
            $forum = new Forum();
            $forum->setCategory($category);
        } else {
            return new JsonResponse(['status' => 'Either category_id or parent_forum_id must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $forum->setName($data['name']);
        $forum->setDescription($data['description']);
        $forum->setBanner($data['banner']);
        $forum->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Forum or Subforum created'], JsonResponse::HTTP_CREATED);
    }


    #[Route('/api/forums/{id}', name: 'update_forum', methods: ['PUT'])]
    public function updateForum(
        Forum $forum,
        Request $request,
        EntityManagerInterface $entityManager,
        ForumRepository $forumRepository,
        ForumCategoryRepository $categoryRepository
    ): JsonResponse {
        // Decode the JSON payload
        $data = json_decode($request->getContent(), true);
    
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }
    
        if (!$forum) {
            return new JsonResponse(['error' => 'Forum not found'], 404);
        }
    
        // Update forum properties
        $forum->setName($data['name'] ?? $forum->getName());
        $forum->setDescription($data['description'] ?? $forum->getDescription());
        $forum->setBanner($data['banner'] ?? $forum->getBanner());
    
        // Remove existing category or subforum association
        $forum->setCategory(null);
        $forum->setForum(null);
    
        if (isset($data['category_id'])) {
            $category = $categoryRepository->find($data['category_id']);
            if ($category) {
                $forum->setCategory($category);
            } else {
                return new JsonResponse(['error' => 'Category not found'], 404);
            }
        }
    
        if (isset($data['parent_forum_id'])) {
            $parentForum = $forumRepository->find($data['parent_forum_id']);
            if ($parentForum) {
                $forum->setForum($parentForum);
            } else {
                return new JsonResponse(['error' => 'Parent forum not found'], 404);
            }
        }
    
        // Save changes
        $entityManager->flush();
    
        return new JsonResponse(['status' => 'Forum updated successfully']);
    }

    #[Route('/api/forums/{id}', name: 'delete_forum', methods: ['DELETE'])]
    public function deleteForum(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $forum = $entityManager->getRepository(Forum::class)->find($id);
    
        if (!$forum) {
            return new JsonResponse(['message' => 'Forum not found'], 404);
        }
    
        // Option 1: Déplacer les threads dans un forum d'archives
        $archiveForum = $entityManager->getRepository(Forum::class)->findOneBy(['name' => 'Archives']);
        
        if ($archiveForum) {
            foreach ($forum->getThreads() as $thread) {
                $thread->setForum($archiveForum);
                $entityManager->persist($thread);
            }
        } else {
            // Option 2: Supprimer tous les threads associés
            foreach ($forum->getThreads() as $thread) {
                $entityManager->remove($thread);
            }
        }
    
        // Gérer les sous-forums (déplacement ou suppression)
        foreach ($forum->getSubforums() as $subForum) {
            $subForum->setForum(null);
            $entityManager->persist($subForum);
        }
    
        // Supprimer le forum
        $entityManager->remove($forum);
        $entityManager->flush();
    
        return new JsonResponse(['message' => 'Forum deleted successfully'], 200);
    }
    
    
}
