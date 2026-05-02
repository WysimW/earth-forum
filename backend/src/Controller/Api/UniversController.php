<?php

namespace App\Controller\Api;

use App\Entity\Univers;
use App\Repository\UniversRepository;
use App\Repository\ForumRepository;
use App\Service\LastPostService;
use App\Service\ForumStatisticsService;
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

    public function __construct(
        UniversRepository $universRepository, 
        ForumRepository $forumRepository,
        LastPostService $lastPostService,
        ForumStatisticsService $forumStatsService
    )
    {
        $this->universRepository = $universRepository;
        $this->forumRepository = $forumRepository;
        $this->lastPostService = $lastPostService;
        $this->forumStatsService = $forumStatsService;
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

            $data[] = [
                'id' => $univers->getId(),
                'name' => $univers->getName(),
                'description' => $univers->getDescription(),
                'slug' => $univers->getSlug(),
                'createdAt' => $univers->getCreatedAt() ? $univers->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'forumCount' => $forumCount,
                'threadCount' => $threadCount,
            ];
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

        $data = [
            'id' => $univers->getId(),
            'name' => $univers->getName(),
            'description' => $univers->getDescription(),
            'slug' => $univers->getSlug(),
            'createdAt' => $univers->getCreatedAt() ? $univers->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'forumCount' => $forumCount,
            'threadCount' => $threadCount,
        ];

        return new JsonResponse($data);
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

        // Organiser les forums par type
        $forumsByType = [
            'important' => [],
            'player_platform' => [],
            'roleplay' => [],
            'hrp' => []
        ];

        foreach ($universeForums as $forum) {
            // Ajouter les informations du dernier post
            $lastPostInfo = $this->lastPostService->getLastPostInfoForForum($forum->getId());
            
            // Ajouter les statistiques cumulées
            $stats = $this->forumStatsService->getForumStats($forum->getId());
            
            // Charger explicitement les sous-forums triés par type et position
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

            // Enrichir les sous-forums
            $subforumsData = [];
            foreach ($subforums as $subforum) {
                $subLastPostInfo = $this->lastPostService->getLastPostInfoForForum($subforum->getId());
                $subStats = $this->forumStatsService->getForumStats($subforum->getId());
                
                $subforumsData[] = [
                    'id' => $subforum->getId(),
                    'slug' => $subforum->getSlug(),
                    'name' => $subforum->getName(),
                    'description' => $subforum->getDescription(),
                    'banner' => $subforum->getBanner(),
                    'heroLogo' => $subforum->getHeroLogo(),
                    'type' => $subforum->getType(),
                    'isRoleplay' => $subforum->isRoleplay(),
                    'stats' => [
                        'totalThreads' => $subStats['thread_count'] ?? 0,
                        'totalPosts' => $subStats['post_count'] ?? 0,
                    ],
                    'lastPost' => $subLastPostInfo ? [
                        'threadId' => $subLastPostInfo['threadId'] ?? null,
                        'threadSlug' => $subLastPostInfo['threadSlug'] ?? null,
                        'threadTitle' => $subLastPostInfo['threadTitle'] ?? null,
                        'author' => $subLastPostInfo['author'] ?? null,
                        'character' => $subLastPostInfo['character'] ?? null,
                        'avatar' => $subLastPostInfo['avatar'] ?? null,
                        'date' => $subLastPostInfo['date'] instanceof \DateTimeInterface 
                            ? $subLastPostInfo['date']->format('Y-m-d H:i:s') 
                            : ($subLastPostInfo['date'] ?? null),
                    ] : null,
                ];
            }

            $forumData = [
                'id' => $forum->getId(),
                'slug' => $forum->getSlug(),
                'name' => $forum->getName(),
                'description' => $forum->getDescription(),
                'banner' => $forum->getBanner(),
                'heroLogo' => $forum->getHeroLogo(),
                'type' => $forum->getType(),
                'isRoleplay' => $forum->isRoleplay(),
                'stats' => [
                    'totalThreads' => $stats['thread_count'] ?? 0,
                    'totalPosts' => $stats['post_count'] ?? 0,
                    'subforumCount' => count($subforums),
                ],
                'subforums' => $subforumsData,
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
            ];

            // Ajouter au bon type
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
                        $subLastPostInfo = $this->lastPostService->getLastPostInfoForForum($subforum->getId());
                        $subStats = $this->forumStatsService->getForumStats($subforum->getId());
                        
                        $subforumsData[] = [
                            'id' => $subforum->getId(),
                            'slug' => $subforum->getSlug(),
                            'name' => $subforum->getName(),
                            'description' => $subforum->getDescription(),
                            'banner' => $subforum->getBanner(),
                            'heroLogo' => $subforum->getHeroLogo(),
                            'type' => $subforum->getType(),
                            'isRoleplay' => $subforum->isRoleplay(),
                            'stats' => [
                                'totalThreads' => $subStats['thread_count'] ?? 0,
                                'totalPosts' => $subStats['post_count'] ?? 0,
                            ],
                            'lastPost' => $subLastPostInfo ? [
                                'threadId' => $subLastPostInfo['threadId'] ?? null,
                                'threadSlug' => $subLastPostInfo['threadSlug'] ?? null,
                                'threadTitle' => $subLastPostInfo['threadTitle'] ?? null,
                                'author' => $subLastPostInfo['author'] ?? null,
                                'character' => $subLastPostInfo['character'] ?? null,
                                'avatar' => $subLastPostInfo['avatar'] ?? null,
                                'date' => $subLastPostInfo['date'] instanceof \DateTimeInterface 
                                    ? $subLastPostInfo['date']->format('Y-m-d H:i:s') 
                                    : ($subLastPostInfo['date'] ?? null),
                            ] : null,
                        ];
                    }

                    $elseworldForumsData[] = [
                        'id' => $forum->getId(),
                        'slug' => $forum->getSlug(),
                        'name' => $forum->getName(),
                        'description' => $forum->getDescription(),
                        'banner' => $forum->getBanner(),
                        'heroLogo' => $forum->getHeroLogo(),
                        'type' => $forum->getType(),
                        'isRoleplay' => $forum->isRoleplay(),
                        'stats' => [
                            'totalThreads' => $stats['thread_count'] ?? 0,
                            'totalPosts' => $stats['post_count'] ?? 0,
                            'subforumCount' => count($subforums),
                        ],
                        'subforums' => $subforumsData,
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
                    ];
                }

                $elseworldsData[] = [
                    'id' => $elseworld->getId(),
                    'name' => $elseworld->getName(),
                    'slug' => $elseworld->getSlug(),
                    'description' => $elseworld->getDescription(),
                    'logo' => $elseworld->getLogo(),
                    'forums' => $elseworldForumsData,
                ];
            }
        }

        return new JsonResponse([
            'universe' => [
                'id' => $univers->getId(),
                'name' => $univers->getName(),
                'slug' => $univers->getSlug(),
                'description' => $univers->getDescription(),
            ],
            'forums' => $forumsByType,
            'elseworlds' => $elseworldsData,
        ]);
    }
}

