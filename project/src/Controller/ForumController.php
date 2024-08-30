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

class ForumController extends AbstractController
{    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/forums/list', name: 'get_forum_list', methods: ['GET'])]
    public function getForumList(): JsonResponse
    {
        $forums = $this->entityManager->getRepository(Forum::class)->createQueryBuilder('f')
            ->select('f.id, f.name')
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse($forums);
    }


#[Route('/api/forums/{id}', name: 'get_forum_detail', methods: ['GET'])]
public function getForumDetail(Forum $forum): JsonResponse
{
    $subForums = [];

    foreach ($forum->getSubforums() as $subForum) {
        $lastThread = $subForum->getLastPostInfo();
        $subForums[] = [
            'id' => $subForum->getId(),
            'name' => $subForum->getName(),
            'description' => $subForum->getDescription(),
            'bannerImage' => $subForum->getBanner(),
            'lastThread' => $lastThread ? [
                'id' => $lastThread['id'] ?? null,
                'title' => $lastThread['title'] ?? null,
                'author' => $lastThread['author'] ?? null,
                'date' => $lastThread['date'] ?? null,
                'threadId' => $lastThread['id'] ?? null,
            ] : null,
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
    }

    $data = [
        'forumId' => $forum->getId(),
        'forumName' => $forum->getName(),
        'description' => $forum->getDescription(),
        'bannerImage' => $forum->getBanner(),
        'breadcrumb' => [
            ['name' => 'Home', 'url' => '/'],
            ['name' => 'DC Universe', 'url' => '/forum/1'],
            ['name' => $forum->getName(), 'url' => "/forum/{$forum->getId()}"]
        ],
        'subForums' => $subForums,
        'threads' => $threads,
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

}
