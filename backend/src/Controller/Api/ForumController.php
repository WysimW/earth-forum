<?php

// src/Controller/ForumController.php

namespace App\Controller\Api;

use App\Entity\Forum;
use App\Entity\ForumCategory;
use App\Entity\User;
use App\Repository\ForumRepository;
use App\Repository\ReadPostRepository;
use App\Repository\UniversRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ForumCategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\AuthorDisplayResolver;
use App\Service\BreadcrumbService;
use App\Service\LastPostService;
use App\Service\S3MediaUrlResolver;
use App\Service\Seo\SeoService;
use App\Repository\ThreadRepository;
use Symfony\Component\String\Slugger\SluggerInterface;


class ForumController extends AbstractController
{
    private const ALLOWED_FORUM_STATUSES = ['open', 'closed', 'archived'];

    private $entityManager;
    private $forumRepository;
    private $categoryRepository;
    private $breadcrumbService;
    private $lastPostService;
    private $threadRepository;
    private ReadPostRepository $readPostRepository;
    private SluggerInterface $slugger;
    private S3MediaUrlResolver $s3MediaUrlResolver;
    private SeoService $seoService;
    private AuthorDisplayResolver $authorDisplayResolver;

    public function __construct(
        EntityManagerInterface $entityManager,
        ForumRepository $forumRepository,
        ForumCategoryRepository $categoryRepository,
        BreadcrumbService $breadcrumbService,
        LastPostService $lastPostService,
        ThreadRepository $threadRepository,
        ReadPostRepository $readPostRepository,
        SluggerInterface $slugger,
        S3MediaUrlResolver $s3MediaUrlResolver,
        SeoService $seoService,
        AuthorDisplayResolver $authorDisplayResolver,
    ) 
    {
        $this->entityManager = $entityManager;
        $this->forumRepository = $forumRepository;
        $this->categoryRepository = $categoryRepository;
        $this->breadcrumbService = $breadcrumbService;
        $this->lastPostService = $lastPostService;
        $this->threadRepository = $threadRepository;
        $this->readPostRepository = $readPostRepository;
        $this->slugger = $slugger;
        $this->s3MediaUrlResolver = $s3MediaUrlResolver;
        $this->seoService = $seoService;
        $this->authorDisplayResolver = $authorDisplayResolver;
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
                'banner' => $this->s3MediaUrlResolver->resolve($forum->getBanner()),
                'category_id' => $forum->getCategory() ? $forum->getCategory()->getId() : null,
                'parent_forum_id' => $forum->getParent() ? $forum->getParent()->getId() : null,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/admin/forums', name: 'get_all_forums', methods: ['GET'])]
    public function getAllForums(Request $request, ForumRepository $forumRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user->getAdminUniverses()->toArray()
        );
        $contextUniverseId = $request->query->get('context_universe_id');
        if ($contextUniverseId && !$isSuperAdmin && !in_array((int) $contextUniverseId, $allowedUniverseIds, true)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $forums = $forumRepository->findAll();

        $data = [];
        $forumsById = [];
        foreach ($forums as $forum) {
            $forumsById[$forum->getId()] = $forum;
        }

        $resolveUniverseId = static function (Forum $forum) use (&$resolveUniverseId, $forumsById): ?int {
            if ($forum->getUniverse()) {
                return $forum->getUniverse()->getId();
            }

            $parent = $forum->getParent();
            if (!$parent) {
                return null;
            }

            $parentId = $parent->getId();
            if (!$parentId || !isset($forumsById[$parentId])) {
                return null;
            }

            return $resolveUniverseId($forumsById[$parentId]);
        };

        foreach ($forums as $forum) {
            $effectiveUniverseId = $resolveUniverseId($forum);
            if (!$isSuperAdmin) {
                // Les forums globaux (sans univers) restent visibles pour tous les admins.
                if ($effectiveUniverseId && !in_array($effectiveUniverseId, $allowedUniverseIds, true)) {
                    continue;
                }
            }

            // Avec un contexte d'univers, on filtre les forums liés à cet univers
            // mais on conserve toujours les forums globaux.
            if ($contextUniverseId && $effectiveUniverseId !== null && (int) $contextUniverseId !== (int) $effectiveUniverseId) {
                continue;
            }

            // Compter les sous-forums
            $subforums = $forumRepository->findBy(['parent' => $forum]);
            
            $data[] = [
                'id' => $forum->getId(),
                'name' => $forum->getName(),
                'description' => $forum->getDescription(),
                'banner' => $this->s3MediaUrlResolver->resolve($forum->getBanner()),
                'universe_id' => $forum->getUniverse() ? $forum->getUniverse()->getId() : null,
                'universe_name' => $forum->getUniverse() ? $forum->getUniverse()->getName() : null,
                'category_id' => $forum->getCategory() ? $forum->getCategory()->getId() : null,
                'parent_forum_id' => $forum->getParent() ? $forum->getParent()->getId() : null,
                'parent_forum_name' => $forum->getParent() ? $forum->getParent()->getName() : null,
                'type' => $forum->getType(),
                'status' => $forum->getStatus(),
                'position' => $forum->getPosition(),
                'subforums_count' => count($subforums),
                'is_parent' => $forum->getParent() === null,
                'seo' => $this->seoService->serializeMetadata($forum->getSeo()),
            ];
        }

        return new JsonResponse($data);
    }


    #[Route('/api/forums/by-slug/{slug}', name: 'get_forum_by_slug', methods: ['GET'])]
    public function getForumBySlug(string $slug, Request $request): JsonResponse
    {
        // Essayer de trouver par slug d'abord, puis par ID si c'est un nombre
        $forum = null;
        if (is_numeric($slug)) {
            $forum = $this->forumRepository->find((int)$slug);
        } else {
            $forum = $this->forumRepository->findOneBy(['slug' => $slug]);
        }

        if (!$forum) {
            return new JsonResponse(['error' => 'Forum non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        if ($forum->getStatus() === 'archived') {
            return new JsonResponse(['error' => 'Forum non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier si on demande la pagination des threads
        $page = $request->query->getInt('page', 0);
        $limit = $request->query->getInt('limit', 0);
        $usePagination = $page > 0 && $limit > 0;

        if ($usePagination) {
            return $this->getForumDetailDataWithPagination($forum, $request);
        }

        return $this->getForumDetailData($forum);
    }

    #[Route('/api/forums/{id}', name: 'get_forum_read', methods: ['GET'])]
    public function getForumDetail(Forum $forum): JsonResponse
    {
        if ($forum->getStatus() === 'archived') {
            return new JsonResponse(['error' => 'Forum non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->getForumDetailData($forum);
    }

    #[Route('/api/forums/{id}/mark-read', name: 'api_forum_mark_read', methods: ['POST'])]
    public function markForumRead(Forum $forum): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser() instanceof User ? $this->getUser() : null;
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $forumIds = $this->collectForumTreeIds($forum);
        $this->readPostRepository->markForumsAsRead($currentUser, $forumIds);

        return new JsonResponse(['status' => 'ok']);
    }

    private function getForumDetailData(Forum $forum): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser() instanceof User ? $this->getUser() : null;
        $subForums = [];
    
        foreach ($forum->getSubforums() as $subForum) {
            if ($subForum->getStatus() === 'archived') {
                continue;
            }
            $id = $subForum->getId();
            
            // Utiliser LastPostService pour récupérer les informations du dernier post
            $lastPostInfo = $this->lastPostService->getLastPostInfoForForum($id);
            
            $stats = $this->forumRepository->countThreadsAndPostsInForum($id);
            $statsData = [
                'totalThreads' => $stats['totalThreads'],
                'totalPosts' => $stats['totalPosts'],
            ];
    
            $latestThreads = $this->forumRepository->findLatestThreads($id);
            $latestThreadsData = [];
            foreach ($latestThreads as $thread) {
                $latestThreadsData[] = [
                    'id' => $thread->getId(),
                    'title' => $thread->getTitle(),
                    'author' => $this->authorDisplayResolver->resolveThreadAuthor($thread)['displayName'],
                    'createdAt' => $thread->getCreatedAt()->format('Y-m-d H:i:s'),
                    // Add more fields as necessary
                ];
            }
    
            $subForums[] = [
                'id' => $subForum->getId(),
                'slug' => $subForum->getSlug(),
                'name' => $subForum->getName(),
                'description' => $subForum->getDescription(),
                'banner' => $this->s3MediaUrlResolver->resolve($subForum->getBanner()),
                'type' => $subForum->getType(),
                'hasUnreadThreads' => false,
                'hasParticipatingUnreadThreads' => false,
                'lastPost' => $lastPostInfo ? [
                    'threadId' => $lastPostInfo['threadId'] ?? null,
                    'threadSlug' => $lastPostInfo['threadSlug'] ?? null,
                    'threadTitle' => $lastPostInfo['threadTitle'] ?? null,
                    'author' => $lastPostInfo['author'] ?? null,
                    'character' => $lastPostInfo['character'] ?? null,
                    'avatar' => $lastPostInfo['avatar'] ?? null,
                    'date' => $lastPostInfo['date'] instanceof \DateTimeInterface 
                        ? $lastPostInfo['date']->format('Y-m-d H:i:s') 
                        : ($lastPostInfo['date'] ?? null),
                ] : null,
                'stats' => $statsData,
                'latestThreads' => $latestThreadsData,
            ];
        }

        if ($currentUser && $subForums !== []) {
            $subForumIds = array_map(static fn (array $subForum): int => (int) $subForum['id'], $subForums);
            $unreadByForumId = $this->readPostRepository->getUnreadCountsForUserAndForums($currentUser, $subForumIds);
            $participatingUnreadByForumId = $this->readPostRepository->getParticipatingUnreadCountsForUserAndForums($currentUser, $subForumIds);
            foreach ($subForums as &$subForumData) {
                $forumId = (int) ($subForumData['id'] ?? 0);
                $subForumData['hasUnreadThreads'] = (($unreadByForumId[$forumId] ?? 0) > 0);
                $subForumData['hasParticipatingUnreadThreads'] = (($participatingUnreadByForumId[$forumId] ?? 0) > 0);
            }
            unset($subForumData);
        }
    
        // Sort threads by the most recent post date
        $threads = $forum->getThreads()->toArray();
        usort($threads, function ($a, $b) {
            $latestPostA = $a->getPosts()->last();
            $latestPostB = $b->getPosts()->last();
        
            // If either thread has no posts, we consider them to be equal
            if (!$latestPostA && !$latestPostB) {
                return 0;
            } elseif (!$latestPostA) {
                return 1;  // No posts in A, so B is considered "newer"
            } elseif (!$latestPostB) {
                return -1;  // No posts in B, so A is considered "newer"
            }
        
            // Compare the createdAt dates of the latest posts in each thread
            return $latestPostB->getCreatedAt() <=> $latestPostA->getCreatedAt();
        });
        
    
        $isRoleplay = $this->forumRepository->isForumOrParentInCategoryType($forum, 'roleplay');
        
        $threadIds = array_map(static fn ($thread) => $thread->getId(), $threads);
        $unreadCountsByThreadId = $currentUser
            ? $this->readPostRepository->getUnreadCountsForUserAndThreads($currentUser, $threadIds)
            : [];
        $participatingThreadIds = $currentUser
            ? $this->threadRepository->findParticipatingThreadIdsForUser($currentUser, $threadIds)
            : [];
        $participatingThreadLookup = array_fill_keys($participatingThreadIds, true);

        $threadsData = [];
        foreach ($threads as $thread) {
            // Utiliser LastPostService pour récupérer les informations du dernier post du thread
            $lastPostInfo = $this->lastPostService->getLastPostInfoForThread($thread->getId());
            
            $threadAuthor = $this->authorDisplayResolver->resolveThreadAuthor($thread);
            $author = $threadAuthor['displayName'];
            $avatar = $threadAuthor['avatar'];
            $character = $threadAuthor['characterName'];
            
            $threadId = $thread->getId();
            $unreadCount = (int) ($unreadCountsByThreadId[$threadId] ?? 0);
            $threadsData[] = [
                'threadId' => $threadId,
                'threadSlug' => $thread->getSlug(),
                'title' => $thread->getTitle(),
                'author' => $author,
                'authorAvatar' => $this->s3MediaUrlResolver->resolve($avatar),
                'authorId' => $threadAuthor['userId'],
                'character' => $character,
                'characterCreatorId' => $thread->getCharacterCreator() && $thread->getCharacterCreator()->getUser() 
                    ? $thread->getCharacterCreator()->getUser()->getId() 
                    : null,
                'status' => $thread->getStatus(),
                'pinned' => $thread->getSticky(),
                'locked' => $thread->getStatus() === 'closed', // Un thread est verrouillé s'il est fermé
                'isUnread' => $unreadCount > 0,
                'unreadCount' => $unreadCount,
                'isParticipant' => isset($participatingThreadLookup[$threadId]),
                'createdAt' => $thread->getCreatedAt()->format('d/m/y H:i'),
                'replies' => $thread->getPosts()->count() - 1,
                'lastPost' => $lastPostInfo ? [
                    'id' => $lastPostInfo['postId'] ?? null,
                    'threadId' => $thread->getId(),
                    'threadSlug' => $thread->getSlug(),
                    'author' => $lastPostInfo['author'] ?? null,
                    'authorId' => $lastPostInfo['authorId'] ?? null,
                    'character' => $lastPostInfo['character'] ?? null,
                    'avatar' => $lastPostInfo['avatar'] ?? null,
                    'date' => $lastPostInfo['date'] instanceof \DateTimeInterface 
                        ? $lastPostInfo['date']->format('Y-m-d H:i:s') 
                        : ($lastPostInfo['date'] ?? null),
                ] : null,
            ];
        }
        
        // Calculer les statistiques du forum principal
        $forumStats = $this->forumRepository->countThreadsAndPostsInForum($forum->getId());
        $statsData = [
            'totalThreads' => $forumStats['totalThreads'],
            'totalPosts' => $forumStats['totalPosts'],
        ];
        
        $breadcrumbs = $this->breadcrumbService->generateBreadcrumbs($forum);
    
        $data = [
            'forumId' => $forum->getId(),
            'forumSlug' => $forum->getSlug(),
            'forumName' => $forum->getName(),
            'type' => $forum->getType(),
            'description' => $forum->getDescription(),
            'bannerImage' => $this->s3MediaUrlResolver->resolve($forum->getBanner()),
            'isRoleplay' => $isRoleplay,
            'breadcrumb' => $breadcrumbs,
            'stats' => $statsData,
            'subForums' => $subForums,
            'threads' => $threadsData,
            'universe' => $forum->getUniverse() ? [
                'id' => $forum->getUniverse()->getId(),
                'name' => $forum->getUniverse()->getName(),
                'slug' => $forum->getUniverse()->getSlug(),
            ] : null,
            'seo' => $this->buildForumSeo($forum),
        ];
    
        return new JsonResponse($data);
    }

    private function getForumDetailDataWithPagination(Forum $forum, Request $request): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser() instanceof User ? $this->getUser() : null;
        // Récupérer les paramètres de pagination et filtres
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $filters = [
            'status' => $request->query->get('status', 'all'),
            'search' => $request->query->get('search', ''),
            'userId' => $request->query->getInt('userId', 0) ?: null,
            'author' => $request->query->getInt('author', 0) ?: null,
            'character' => $request->query->getInt('character', 0) ?: null,
        ];

        // Récupérer les threads avec pagination
        $result = $this->threadRepository->findThreadsByForumWithPagination(
            $forum->getId(),
            $filters,
            $page,
            $limit
        );

        // Récupérer les sous-forums (même logique que getForumDetailData)
        $subForums = [];
        foreach ($forum->getSubforums() as $subForum) {
            if ($subForum->getStatus() === 'archived') {
                continue;
            }
            $id = $subForum->getId();
            $lastPostInfo = $this->lastPostService->getLastPostInfoForForum($id);
            $stats = $this->forumRepository->countThreadsAndPostsInForum($id);
            $statsData = [
                'totalThreads' => $stats['totalThreads'],
                'totalPosts' => $stats['totalPosts'],
            ];
            
            $subForums[] = [
                'id' => $subForum->getId(),
                'slug' => $subForum->getSlug(),
                'name' => $subForum->getName(),
                'description' => $subForum->getDescription(),
                'banner' => $this->s3MediaUrlResolver->resolve($subForum->getBanner()),
                'hasUnreadThreads' => false,
                'hasParticipatingUnreadThreads' => false,
                'lastPost' => $lastPostInfo ? [
                    'threadId' => $lastPostInfo['threadId'] ?? null,
                    'threadSlug' => $lastPostInfo['threadSlug'] ?? null,
                    'threadTitle' => $lastPostInfo['threadTitle'] ?? null,
                    'author' => $lastPostInfo['author'] ?? null,
                    'character' => $lastPostInfo['character'] ?? null,
                    'avatar' => $lastPostInfo['avatar'] ?? null,
                    'date' => $lastPostInfo['date'] instanceof \DateTimeInterface 
                        ? $lastPostInfo['date']->format('Y-m-d H:i:s') 
                        : ($lastPostInfo['date'] ?? null),
                ] : null,
                'stats' => $statsData,
            ];
        }

        if ($currentUser && $subForums !== []) {
            $subForumIds = array_map(static fn (array $subForum): int => (int) $subForum['id'], $subForums);
            $unreadByForumId = $this->readPostRepository->getUnreadCountsForUserAndForums($currentUser, $subForumIds);
            $participatingUnreadByForumId = $this->readPostRepository->getParticipatingUnreadCountsForUserAndForums($currentUser, $subForumIds);
            foreach ($subForums as &$subForumData) {
                $forumId = (int) ($subForumData['id'] ?? 0);
                $subForumData['hasUnreadThreads'] = (($unreadByForumId[$forumId] ?? 0) > 0);
                $subForumData['hasParticipatingUnreadThreads'] = (($participatingUnreadByForumId[$forumId] ?? 0) > 0);
            }
            unset($subForumData);
        }

        // Serialiser les threads
        $isRoleplay = $this->forumRepository->isForumOrParentInCategoryType($forum, 'roleplay');
        $threadIds = array_map(static fn ($thread) => $thread->getId(), $result['threads']);
        $unreadCountsByThreadId = $currentUser
            ? $this->readPostRepository->getUnreadCountsForUserAndThreads($currentUser, $threadIds)
            : [];
        $participatingThreadIds = $currentUser
            ? $this->threadRepository->findParticipatingThreadIdsForUser($currentUser, $threadIds)
            : [];
        $participatingThreadLookup = array_fill_keys($participatingThreadIds, true);
        $threadsData = [];
        
        foreach ($result['threads'] as $thread) {
            $lastPostInfo = $this->lastPostService->getLastPostInfoForThread($thread->getId());
            
            $threadAuthor = $this->authorDisplayResolver->resolveThreadAuthor($thread);
            $author = $threadAuthor['displayName'];
            $avatar = $threadAuthor['avatar'];
            $character = $threadAuthor['characterName'];
            
            $threadId = $thread->getId();
            $unreadCount = (int) ($unreadCountsByThreadId[$threadId] ?? 0);
            $threadsData[] = [
                'threadId' => $threadId,
                'threadSlug' => $thread->getSlug(),
                'title' => $thread->getTitle(),
                'author' => $author,
                'authorAvatar' => $this->s3MediaUrlResolver->resolve($avatar),
                'authorId' => $threadAuthor['userId'],
                'character' => $character,
                'characterCreatorId' => $thread->getCharacterCreator() && $thread->getCharacterCreator()->getUser() 
                    ? $thread->getCharacterCreator()->getUser()->getId() 
                    : null,
                'type' => $thread->getType(),
                'status' => $thread->getStatus(),
                'pinned' => $thread->getSticky(),
                'locked' => $thread->getStatus() === 'closed', // Un thread est verrouillé s'il est fermé
                'isUnread' => $unreadCount > 0,
                'unreadCount' => $unreadCount,
                'isParticipant' => isset($participatingThreadLookup[$threadId]),
                'createdAt' => $thread->getCreatedAt()->format('d/m/y H:i'),
                'replies' => $thread->getPosts()->count() - 1, // -1 pour exclure le premier post
                'lastPost' => $lastPostInfo ? [
                    'id' => $lastPostInfo['postId'] ?? null,
                    'threadId' => $thread->getId(),
                    'threadSlug' => $thread->getSlug(),
                    'author' => $lastPostInfo['author'] ?? null,
                    'authorId' => $lastPostInfo['authorId'] ?? null,
                    'character' => $lastPostInfo['character'] ?? null,
                    'avatar' => $lastPostInfo['avatar'] ?? null,
                    'date' => $lastPostInfo['date'] instanceof \DateTimeInterface 
                        ? $lastPostInfo['date']->format('Y-m-d H:i:s') 
                        : ($lastPostInfo['date'] ?? null),
                ] : null,
            ];
        }

        // Calculer les statistiques du forum principal
        $forumStats = $this->forumRepository->countThreadsAndPostsInForum($forum->getId());
        $statsData = [
            'totalThreads' => $forumStats['totalThreads'],
            'totalPosts' => $forumStats['totalPosts'],
        ];
        
        $breadcrumbs = $this->breadcrumbService->generateBreadcrumbs($forum);
    
        $data = [
            'forumId' => $forum->getId(),
            'forumSlug' => $forum->getSlug(),
            'forumName' => $forum->getName(),
            'type' => $forum->getType(),
            'description' => $forum->getDescription(),
            'bannerImage' => $this->s3MediaUrlResolver->resolve($forum->getBanner()),
            'isRoleplay' => $isRoleplay,
            'breadcrumb' => $breadcrumbs,
            'stats' => $statsData,
            'subForums' => $subForums,
            'threads' => $threadsData,
            'pagination' => [
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
                'totalPages' => $result['totalPages'],
            ],
            'filters' => $filters,
            'universe' => $forum->getUniverse() ? [
                'id' => $forum->getUniverse()->getId(),
                'name' => $forum->getUniverse()->getName(),
                'slug' => $forum->getUniverse()->getSlug(),
            ] : null,
            'seo' => $this->buildForumSeo($forum),
        ];
    
        return new JsonResponse($data);
    }

    /**
     * @return array{metaTitle: string, metaDescription: string, ogImage: ?string, robotsIndex: bool, canonical: string}
     */
    private function buildForumSeo(Forum $forum): array
    {
        return $this->seoService->resolveFromMetadata(
            $forum->getSeo(),
            $forum->getName() . ' | Earth Forum',
            $forum->getDescription(),
            $forum->getBanner() ?? $forum->getHeroLogo(),
            '/forums/' . ($forum->getSlug() ?? (string) $forum->getId())
        );
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
            'banner' => $this->s3MediaUrlResolver->resolve($forum->getBanner()),
            'category_id' => $forum->getCategory() ? $forum->getCategory()->getId() : null,
            'parent_forum_id' => $forum->getParent() ? $forum->getParent()->getId() : null,
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
            $forum->setParent($parentForum);
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
        $forum->setSlug($this->generateUniqueSlug((string) $data['name']));
        $forum->setDescription($data['description'] ?? null);
        $forum->setBanner($this->normalizeMediaUrl($data['banner'] ?? null));
        if (isset($data['type'])) {
            $forum->setType($data['type']);
        }
        if (isset($data['status'])) {
            $status = (string) $data['status'];
            if (!in_array($status, self::ALLOWED_FORUM_STATUSES, true)) {
                return new JsonResponse(['error' => 'Statut de forum invalide'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $forum->setStatus($status);
        }

        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Forum or Subforum created'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/admin/forums', name: 'admin_create_forum', methods: ['POST'])]
    public function adminCreateForum(Request $request, ForumRepository $forumRepository, UniversRepository $universRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user->getAdminUniverses()->toArray()
        );

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name'])) {
            return new JsonResponse(['error' => 'Name is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $forum = new Forum();
        $forum->setName($data['name']);
        $forum->setSlug($this->generateUniqueSlug((string) $data['name']));
        $forum->setDescription($data['description'] ?? null);
        $forum->setBanner($this->normalizeMediaUrl($data['banner'] ?? null));
        $forum->setType($data['type'] ?? 'hrp');
        if (isset($data['status'])) {
            $status = (string) $data['status'];
            if (!in_array($status, self::ALLOWED_FORUM_STATUSES, true)) {
                return new JsonResponse(['error' => 'Statut de forum invalide'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $forum->setStatus($status);
        }

        if (!empty($data['parent_forum_id'])) {
            $parentForum = $forumRepository->find($data['parent_forum_id']);
            if (!$parentForum) {
                return new JsonResponse(['error' => 'Parent forum not found'], JsonResponse::HTTP_NOT_FOUND);
            }
            if (!$isSuperAdmin) {
                $parentUniverseId = $parentForum->getUniverse()?->getId();
                if (!$parentUniverseId || !in_array($parentUniverseId, $allowedUniverseIds, true)) {
                    return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
                }
            }
            $forum->setParent($parentForum);
            // Hériter de l'univers du parent
            if ($parentForum->getUniverse()) {
                $forum->setUniverse($parentForum->getUniverse());
            }
        } elseif (!empty($data['universe_id'])) {
            if (!$isSuperAdmin && !in_array((int) $data['universe_id'], $allowedUniverseIds, true)) {
                return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
            }
            $universe = $universRepository->find($data['universe_id']);
            if (!$universe) {
                return new JsonResponse(['error' => 'Universe not found'], JsonResponse::HTTP_NOT_FOUND);
            }
            $forum->setUniverse($universe);
        } elseif (!empty($data['category_id'])) {
            $category = $this->categoryRepository->find($data['category_id']);
            if (!$category) {
                return new JsonResponse(['error' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
            }
            $forum->setCategory($category);
        } else {
            return new JsonResponse(['error' => 'Either parent_forum_id, universe_id or category_id must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Définir la position (à la fin par défaut)
        if (isset($data['position'])) {
            $forum->setPosition((int)$data['position']);
        } else {
            // Trouver la dernière position
            $lastPosition = $forumRepository->createQueryBuilder('f')
                ->select('MAX(f.position)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            $forum->setPosition($lastPosition + 1);
        }

        if (isset($data['seo']) && is_array($data['seo'])) {
            $this->seoService->applySeoInput($forum->getSeo(), $data['seo']);
        }

        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => !empty($data['parent_forum_id']) ? 'Sous-forum créé avec succès' : 'Forum créé avec succès',
            'id' => $forum->getId()
        ], JsonResponse::HTTP_CREATED);
    }


    #[Route('/api/admin/forums/{id}', name: 'update_forum', methods: ['PUT'])]
    public function updateForum(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ForumRepository $forumRepository,
        ForumCategoryRepository $categoryRepository,
        UniversRepository $universRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user->getAdminUniverses()->toArray()
        );

        $forum = $forumRepository->find($id);
        
        if (!$forum) {
            return new JsonResponse(['error' => 'Forum not found'], 404);
        }

        // Decode the JSON payload
        $data = json_decode($request->getContent(), true);
    
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $currentUniverseId = $forum->getUniverse()?->getId();
        if (!$isSuperAdmin) {
            // Si le forum est déjà lié à un univers, l'admin doit y avoir accès.
            if ($currentUniverseId !== null && !in_array($currentUniverseId, $allowedUniverseIds, true)) {
                return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
            }

            // Si une cible universe_id est demandée, elle doit aussi être autorisée.
            if (array_key_exists('universe_id', $data) && $data['universe_id'] !== null) {
                $requestedUniverseId = (int) $data['universe_id'];
                if (!in_array($requestedUniverseId, $allowedUniverseIds, true)) {
                    return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
                }
            }
        }
    
        // Update forum properties
        if (isset($data['name'])) {
            $forum->setName($data['name']);
        }
        if (isset($data['description'])) {
            $forum->setDescription($data['description']);
        }
        if (isset($data['banner'])) {
            $forum->setBanner($this->normalizeMediaUrl($data['banner']));
        }
        if (isset($data['type'])) {
            $forum->setType($data['type']);
        }
        if (isset($data['position'])) {
            $forum->setPosition((int)$data['position']);
        }
        if (isset($data['status'])) {
            $status = (string) $data['status'];
            if (!in_array($status, self::ALLOWED_FORUM_STATUSES, true)) {
                return new JsonResponse(['error' => 'Statut de forum invalide'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $forum->setStatus($status);
        }
    
        // Handle category
        if (isset($data['category_id'])) {
            if ($data['category_id'] === null) {
                $forum->setCategory(null);
            } else {
                $category = $categoryRepository->find($data['category_id']);
                if ($category) {
                    $forum->setCategory($category);
                } else {
                    return new JsonResponse(['error' => 'Category not found'], 404);
                }
            }
        }
    
        // Handle parent forum
        if (isset($data['parent_forum_id'])) {
            if ($data['parent_forum_id'] === null) {
                $forum->setParent(null);
            } else {
                $parentForum = $forumRepository->find($data['parent_forum_id']);
                if ($parentForum) {
                    if (!$isSuperAdmin) {
                        $parentUniverseId = $parentForum->getUniverse()?->getId();
                        if (!$parentUniverseId || !in_array($parentUniverseId, $allowedUniverseIds, true)) {
                            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
                        }
                    }
                    $forum->setParent($parentForum);
                    // Un sous-forum hérite toujours de l'univers du parent
                    $forum->setUniverse($parentForum->getUniverse());
                } else {
                    return new JsonResponse(['error' => 'Parent forum not found'], 404);
                }
            }
        }

        // Handle universe (uniquement pour les forums sans parent)
        if (array_key_exists('universe_id', $data)) {
            if ($forum->getParent()) {
                return new JsonResponse([
                    'error' => 'Impossible de modifier l\'univers d\'un sous-forum : il hérite du forum parent'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if ($data['universe_id'] === null) {
                $forum->setUniverse(null);
            } else {
                $targetUniverseId = (int) $data['universe_id'];
                if (!$isSuperAdmin && !in_array($targetUniverseId, $allowedUniverseIds, true)) {
                    return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
                }

                $universe = $universRepository->find($targetUniverseId);
                if (!$universe) {
                    return new JsonResponse(['error' => 'Universe not found'], JsonResponse::HTTP_NOT_FOUND);
                }
                $forum->setUniverse($universe);
            }
        }

        if (isset($data['seo']) && is_array($data['seo'])) {
            $this->seoService->applySeoInput($forum->getSeo(), $data['seo']);
        }
    
        // Save changes
        $entityManager->flush();
    
        return new JsonResponse(['status' => 'Forum updated successfully']);
    }

    #[Route('/api/forums/{id}', name: 'delete_forum', methods: ['DELETE'])]
    public function deleteForum(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        return $this->deleteForumInternal($id, $entityManager);
    }

    #[Route('/api/admin/forums/{id}', name: 'admin_delete_forum', methods: ['DELETE'])]
    public function adminDeleteForum(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        return $this->deleteForumInternal($id, $entityManager);
    }

    private function deleteForumInternal(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user->getAdminUniverses()->toArray()
        );

        $forumRepository = $entityManager->getRepository(Forum::class);
        $forum = $forumRepository->find($id);
    
        if (!$forum) {
            return new JsonResponse(['message' => 'Forum not found'], 404);
        }

        $forumUniverseId = $forum->getUniverse()?->getId();
        if (!$isSuperAdmin && (!$forumUniverseId || !in_array($forumUniverseId, $allowedUniverseIds, true))) {
            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }
    
        // Déplacer systématiquement les threads vers un forum d'archives
        $archiveForum = $forumRepository->findOneBy(['slug' => 'archives']) ?? $forumRepository->findOneBy(['name' => 'Archives']);

        // Empêcher la suppression si le forum ciblé EST le forum d'archives
        if ($archiveForum && $archiveForum->getId() === $forum->getId()) {
            return new JsonResponse(['error' => 'Le forum d\'archives ne peut pas être supprimé'], 409);
        }

        if (!$archiveForum) {
            $archiveForum = new Forum();
            $archiveForum->setName('Archives');
            $archiveForum->setSlug('archives');
            $archiveForum->setDescription('Forum d\'archivage automatique des sujets');
            $archiveForum->setType('hrp');
            $archiveForum->setStatus('archived');

            $maxPosition = $forumRepository->createQueryBuilder('f')
                ->select('MAX(f.position)')
                ->getQuery()
                ->getSingleScalarResult();

            $archiveForum->setPosition(((int) ($maxPosition ?? 0)) + 1);
            $entityManager->persist($archiveForum);
        }

        foreach ($forum->getThreads() as $thread) {
            $thread->setForum($archiveForum);
            $entityManager->persist($thread);
        }
    
        // Gérer les sous-forums (déplacement ou suppression)
        foreach ($forum->getSubforums() as $subForum) {
            $subForum->setParent(null);
            $entityManager->persist($subForum);
        }
    
        // Supprimer le forum
        $entityManager->remove($forum);
        $entityManager->flush();
    
        return new JsonResponse(['message' => 'Forum deleted successfully'], 200);
    }

    #[Route('/api/admin/forums/update-positions', name: 'api_update_forum_positions', methods: ['POST'])]
    public function updatePositions(Request $request, EntityManagerInterface $entityManager, ForumRepository $forumRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !is_array($data)) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        try {
            foreach ($data as $item) {
                if (!isset($item['id']) || !isset($item['position'])) {
                    continue;
                }
                
                $forum = $forumRepository->find($item['id']);
                if ($forum) {
                    $forum->setPosition((int)$item['position']);
                }
            }
            
            $entityManager->flush();
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @return int[]
     */
    private function collectForumTreeIds(Forum $forum): array
    {
        $ids = [$forum->getId()];
        foreach ($forum->getSubforums() as $subforum) {
            $ids = array_merge($ids, $this->collectForumTreeIds($subforum));
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = $this->slugger->slug($name)->lower()->toString();
        if ($baseSlug === '') {
            $baseSlug = 'forum';
        }

        $candidate = $baseSlug;
        $counter = 2;
        while ($this->forumRepository->findOneBy(['slug' => $candidate]) !== null) {
            $candidate = sprintf('%s-%d', $baseSlug, $counter);
            $counter++;
        }

        return $candidate;
    }

    private function normalizeMediaUrl(mixed $value): ?string
    {
        return $this->s3MediaUrlResolver->normalizeStoredUrl(is_string($value) ? $value : null);
    }

}
