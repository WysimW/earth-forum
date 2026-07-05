<?php

namespace App\Controller\Api;

use App\Entity\Forum;
use App\Entity\Univers;
use App\Entity\User;
use App\Repository\ForumCategoryRepository;
use App\Repository\UniversRepository;
use App\Repository\ForumRepository;
use App\Repository\ReadPostRepository;
use App\Service\ForumStatisticsService;
use App\Service\LastPostService;
use App\Service\S3MediaUrlResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/universes')]
class UniversController extends AbstractController
{
    private $universRepository;
    private $forumRepository;
    private $lastPostService;
    private $forumStatsService;
    private ReadPostRepository $readPostRepository;
    private S3MediaUrlResolver $s3MediaUrlResolver;
    private ForumCategoryRepository $forumCategoryRepository;

    public function __construct(
        UniversRepository $universRepository, 
        ForumRepository $forumRepository,
        LastPostService $lastPostService,
        ForumStatisticsService $forumStatsService,
        ReadPostRepository $readPostRepository,
        S3MediaUrlResolver $s3MediaUrlResolver,
        ForumCategoryRepository $forumCategoryRepository,
    )
    {
        $this->universRepository = $universRepository;
        $this->forumRepository = $forumRepository;
        $this->lastPostService = $lastPostService;
        $this->forumStatsService = $forumStatsService;
        $this->readPostRepository = $readPostRepository;
        $this->s3MediaUrlResolver = $s3MediaUrlResolver;
        $this->forumCategoryRepository = $forumCategoryRepository;
    }

    #[Route('', name: 'api_universes_list', methods: ['GET'])]
    public function getUniverses(): JsonResponse
    {
        $universes = $this->universRepository->findAll();

        $data = [];
        foreach ($universes as $univers) {
            // Compter le nombre de forums dans cet univers
            $forumCount = $this->forumRepository->count(['universe' => $univers]);
            
            // Compter le nombre total de threads dans tous les forums de cet univers
            $forums = $this->forumRepository->findBy(['universe' => $univers]);
            $threadCount = 0;
            foreach ($forums as $forum) {
                $threadCount += $forum->getThreads()->count();
            }

            $data[] = $this->serializeUniversPublic($univers, $forumCount, $threadCount);
        }

        return new JsonResponse(['universes' => $data]);
    }

    #[Route('/{id}', name: 'api_universe_detail', methods: ['GET'])]
    public function getUniversDetail(Univers $univers): JsonResponse
    {
        $forumCount = $this->forumRepository->count(['universe' => $univers]);
        
        $forums = $this->forumRepository->findBy(['universe' => $univers]);
        $threadCount = 0;
        foreach ($forums as $forum) {
            $threadCount += $forum->getThreads()->count();
        }

        return new JsonResponse($this->serializeUniversPublic($univers, $forumCount, $threadCount));
    }

    #[Route('/{slug}/forums', name: 'api_univers_forums', methods: ['GET'])]
    public function getUniversForums(string $slug): JsonResponse
    {
        $univers = $this->universRepository->findOneBy(['slug' => $slug]);
        
        if (!$univers) {
            return new JsonResponse(['error' => 'Univers introuvable'], 404);
        }

        // Récupérer uniquement les forums parents (sans parent) de l'univers, triés par type et position
        $universeForums = $this->forumRepository->createQueryBuilder('f')
            ->where('f.parent IS NULL')
            ->andWhere('f.elseworld IS NULL')
            ->andWhere('f.status != :archivedStatus')
            ->andWhere('(f.universe = :universe OR (f.universe IS NULL AND f.type IN (:globalForumTypes)))')
            ->setParameter('archivedStatus', 'archived')
            ->setParameter('universe', $univers)
            ->setParameter('globalForumTypes', ['important', 'hrp'])
            ->orderBy('CASE f.type 
                WHEN \'important\' THEN 1 
                WHEN \'player_platform\' THEN 2
                WHEN \'roleplay\' THEN 3 
                WHEN \'hrp\' THEN 4 
                ELSE 5 END', 'ASC')
            ->addOrderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();

        /** @var User|null $currentUser */
        $currentUser = $this->getUser() instanceof User ? $this->getUser() : null;
        $allForumIds = $this->collectForumIdsWithChildren($universeForums);

        // Organiser les forums par type (rétrocompatibilité) et par catégorie
        $forumsByType = [
            'important' => [],
            'player_platform' => [],
            'roleplay' => [],
            'hrp' => []
        ];
        $forumsDataByCategoryId = [];

        foreach ($universeForums as $forum) {
            $forumData = $this->buildForumDataArray($forum);

            if ($forum->getType() === 'roleplay' || $forum->isRoleplay()) {
                $categoryId = $forum->getCategory()?->getId();
                if ($categoryId !== null) {
                    $forumsDataByCategoryId[$categoryId][] = $forumData;
                }
            }

            $forumType = $forum->getType();
            if ($forumType === 'important') {
                $forumsByType['important'][] = $forumData;
            } elseif ($forumType === 'player_platform') {
                $forumsByType['player_platform'][] = $forumData;
            } elseif ($forumType === 'roleplay') {
                $forumsByType['roleplay'][] = $forumData;
            } else {
                $forumsByType['hrp'][] = $forumData;
            }
        }

        $roleplayCategories = $this->buildRoleplayCategoriesWithForums($forumsDataByCategoryId);

        // Récupérer les elseworlds
        $elseworlds = $univers->getElseworlds();
        $elseworldsData = [];

        foreach ($elseworlds as $elseworld) {
            $elseworldForums = $this->forumRepository->createQueryBuilder('f')
                ->where('f.parent IS NULL')
                ->andWhere('f.elseworld = :elseworld')
                ->andWhere('f.status != :archivedStatus')
                ->setParameter('elseworld', $elseworld)
                ->setParameter('archivedStatus', 'archived')
                ->orderBy('CASE f.type 
                    WHEN \'important\' THEN 1 
                    WHEN \'player_platform\' THEN 2
                    WHEN \'roleplay\' THEN 3 
                    WHEN \'hrp\' THEN 4 
                    ELSE 5 END', 'ASC')
                ->addOrderBy('f.position', 'ASC')
                ->getQuery()
                ->getResult();

            if (count($elseworldForums) > 0) {
                $elseworldForumsData = [];
                foreach ($elseworldForums as $forum) {
                    $elseworldForumsData[] = $this->buildForumDataArray($forum);
                }

                $elseworldsData[] = [
                    'id' => $elseworld->getId(),
                    'name' => $elseworld->getName(),
                    'slug' => $elseworld->getSlug(),
                    'description' => $elseworld->getDescription(),
                    'logo' => $this->s3MediaUrlResolver->resolve($elseworld->getLogo()),
                    'forums' => $elseworldForumsData,
                ];
            }
        }

        if ($currentUser && $allForumIds !== []) {
            $unreadByForumId = $this->readPostRepository->getUnreadCountsForUserAndForums($currentUser, $allForumIds);
            $participatingUnreadByForumId = $this->readPostRepository->getParticipatingUnreadCountsForUserAndForums($currentUser, $allForumIds);

            $applyFlags = function (array &$forumItem) use (&$applyFlags, $unreadByForumId, $participatingUnreadByForumId): void {
                $forumId = (int) ($forumItem['id'] ?? 0);
                $forumItem['hasUnreadThreads'] = (($unreadByForumId[$forumId] ?? 0) > 0);
                $forumItem['hasParticipatingUnreadThreads'] = (($participatingUnreadByForumId[$forumId] ?? 0) > 0);
                if (!empty($forumItem['subforums']) && is_array($forumItem['subforums'])) {
                    foreach ($forumItem['subforums'] as &$subforum) {
                        $applyFlags($subforum);
                    }
                    unset($subforum);
                }
            };

            foreach ($forumsByType as &$forumsOfType) {
                foreach ($forumsOfType as &$forumItem) {
                    $applyFlags($forumItem);
                }
                unset($forumItem);
            }
            unset($forumsOfType);

            foreach ($roleplayCategories as &$categoryItem) {
                foreach ($categoryItem['forums'] as &$forumItem) {
                    $applyFlags($forumItem);
                }
                unset($forumItem);
            }
            unset($categoryItem);

            foreach ($elseworldsData as &$elseworldItem) {
                foreach ($elseworldItem['forums'] as &$forumItem) {
                    $applyFlags($forumItem);
                }
                unset($forumItem);
            }
            unset($elseworldItem);
        }

        return new JsonResponse([
            'universe' => [
                'id' => $univers->getId(),
                'name' => $univers->getName(),
                'slug' => $univers->getSlug(),
                'description' => $univers->getDescription(),
                'forumsTitle' => $univers->getForumsTitle(),
                'forumsHeaderBanner' => $this->s3MediaUrlResolver->resolve($univers->getForumsHeaderBanner()),
                'portalBanner' => $this->s3MediaUrlResolver->resolve($univers->getPortalBanner()),
            ],
            'forums' => $forumsByType,
            'roleplayCategories' => $roleplayCategories,
            'elseworlds' => $elseworldsData,
        ]);
    }

    #[Route('/{slug}/mark-read', name: 'api_universe_mark_read', methods: ['POST'])]
    public function markUniverseRead(string $slug): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser() instanceof User ? $this->getUser() : null;
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $univers = $this->universRepository->findOneBy(['slug' => $slug]);
        if (!$univers) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $universeForums = $this->forumRepository->createQueryBuilder('f')
            ->where('f.parent IS NULL')
            ->andWhere('f.elseworld IS NULL')
            ->andWhere('f.status != :archivedStatus')
            ->andWhere('(f.universe = :universe OR (f.universe IS NULL AND f.type IN (:globalForumTypes)))')
            ->setParameter('archivedStatus', 'archived')
            ->setParameter('universe', $univers)
            ->setParameter('globalForumTypes', ['important', 'hrp'])
            ->getQuery()
            ->getResult();

        $forumIds = $this->collectForumIdsWithChildren($universeForums);
        foreach ($univers->getElseworlds() as $elseworld) {
            $elseworldForums = $this->forumRepository->createQueryBuilder('f')
                ->where('f.parent IS NULL')
                ->andWhere('f.elseworld = :elseworld')
                ->andWhere('f.status != :archivedStatus')
                ->setParameter('elseworld', $elseworld)
                ->setParameter('archivedStatus', 'archived')
                ->getQuery()
                ->getResult();
            $forumIds = array_merge($forumIds, $this->collectForumIdsWithChildren($elseworldForums));
        }

        $this->readPostRepository->markForumsAsRead($currentUser, array_values(array_unique($forumIds)));

        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * @param array<int,\App\Entity\Forum> $forums
     * @return int[]
     */
    private function collectForumIdsWithChildren(array $forums): array
    {
        $ids = [];
        foreach ($forums as $forum) {
            $ids[] = $forum->getId();
            $children = $this->forumRepository->createQueryBuilder('sf')
                ->where('sf.parent = :parent')
                ->andWhere('sf.status != :archivedStatus')
                ->setParameter('parent', $forum)
                ->setParameter('archivedStatus', 'archived')
                ->getQuery()
                ->getResult();
            $ids = array_merge($ids, $this->collectForumIdsWithChildren($children));
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $forumsDataByCategoryId
     * @return array<int, array<string, mixed>>
     */
    private function buildRoleplayCategoriesWithForums(array $forumsDataByCategoryId): array
    {
        $roleplayCategories = [];
        $allCategories = $this->forumCategoryRepository->findBy([], ['homeOrder' => 'ASC', 'id' => 'ASC']);

        foreach ($allCategories as $category) {
            if ($category->getType()?->getSlug() !== 'roleplay') {
                continue;
            }

            $categoryForums = $forumsDataByCategoryId[$category->getId()] ?? [];
            if ($categoryForums === []) {
                continue;
            }

            $roleplayCategories[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'slug' => $category->getSlug(),
                'order' => $category->getHomeOrder(),
                'forums' => $categoryForums,
            ];
        }

        return $roleplayCategories;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildForumDataArray(Forum $forum): array
    {
        $lastPostInfo = $this->lastPostService->getLastPostInfoForForum($forum->getId());
        $stats = $this->forumStatsService->getForumStats($forum->getId());
        $subforums = $this->forumRepository->createQueryBuilder('sf')
            ->where('sf.parent = :parent')
            ->andWhere('sf.status != :archivedStatus')
            ->setParameter('parent', $forum)
            ->setParameter('archivedStatus', 'archived')
            ->orderBy('CASE sf.type 
                WHEN \'important\' THEN 1 
                WHEN \'player_platform\' THEN 2
                WHEN \'roleplay\' THEN 3 
                WHEN \'hrp\' THEN 4 
                ELSE 5 END', 'ASC')
            ->addOrderBy('sf.position', 'ASC')
            ->getQuery()
            ->getResult();

        $subforumsData = [];
        foreach ($subforums as $subforum) {
            $subforumsData[] = $this->buildSubforumDataArray($subforum);
        }

        return [
            'id' => $forum->getId(),
            'slug' => $forum->getSlug(),
            'name' => $forum->getName(),
            'description' => $forum->getDescription(),
            'banner' => $this->s3MediaUrlResolver->resolve($forum->getBanner()),
            'heroLogo' => $this->s3MediaUrlResolver->resolve($forum->getHeroLogo()),
            'type' => $forum->getType(),
            'isRoleplay' => $forum->isRoleplay(),
            'stats' => [
                'totalThreads' => $stats['thread_count'] ?? 0,
                'totalPosts' => $stats['post_count'] ?? 0,
                'subforumCount' => count($subforums),
            ],
            'hasUnreadThreads' => false,
            'hasParticipatingUnreadThreads' => false,
            'subforums' => $subforumsData,
            'lastPost' => $this->formatLastPostPayload($lastPostInfo),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSubforumDataArray(Forum $subforum): array
    {
        $subLastPostInfo = $this->lastPostService->getLastPostInfoForForum($subforum->getId());
        $subStats = $this->forumStatsService->getForumStats($subforum->getId());

        return [
            'id' => $subforum->getId(),
            'slug' => $subforum->getSlug(),
            'name' => $subforum->getName(),
            'description' => $subforum->getDescription(),
            'banner' => $this->s3MediaUrlResolver->resolve($subforum->getBanner()),
            'heroLogo' => $this->s3MediaUrlResolver->resolve($subforum->getHeroLogo()),
            'type' => $subforum->getType(),
            'isRoleplay' => $subforum->isRoleplay(),
            'stats' => [
                'totalThreads' => $subStats['thread_count'] ?? 0,
                'totalPosts' => $subStats['post_count'] ?? 0,
            ],
            'hasUnreadThreads' => false,
            'hasParticipatingUnreadThreads' => false,
            'lastPost' => $this->formatLastPostPayload($subLastPostInfo),
        ];
    }

    /**
     * @param array<string, mixed>|null $lastPostInfo
     * @return array<string, mixed>|null
     */
    private function formatLastPostPayload(?array $lastPostInfo): ?array
    {
        if (!$lastPostInfo) {
            return null;
        }

        return [
            'threadId' => $lastPostInfo['threadId'] ?? null,
            'threadSlug' => $lastPostInfo['threadSlug'] ?? null,
            'threadTitle' => $lastPostInfo['threadTitle'] ?? null,
            'author' => $lastPostInfo['author'] ?? null,
            'character' => $lastPostInfo['character'] ?? null,
            'avatar' => $lastPostInfo['avatar'] ?? null,
            'date' => $lastPostInfo['date'] instanceof \DateTimeInterface
                ? $lastPostInfo['date']->format('Y-m-d H:i:s')
                : ($lastPostInfo['date'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUniversPublic(Univers $univers, int $forumCount, int $threadCount): array
    {
        $portalBanner = $this->s3MediaUrlResolver->resolve($univers->getPortalBanner());

        return [
            'id' => $univers->getId(),
            'name' => $univers->getName(),
            'description' => $univers->getDescription(),
            'slug' => $univers->getSlug(),
            'forumsTitle' => $univers->getForumsTitle(),
            'forumsHeaderBanner' => $this->s3MediaUrlResolver->resolve($univers->getForumsHeaderBanner()),
            'portalBanner' => $portalBanner,
            'banner' => $portalBanner,
            'backgroundImage' => $portalBanner,
            'createdAt' => $univers->getCreatedAt()?->format('Y-m-d H:i:s'),
            'forumCount' => $forumCount,
            'threadCount' => $threadCount,
        ];
    }
}

