<?php

namespace App\Service;

use App\Entity\Forum;
use App\Repository\ForumRepository;
use App\Repository\PostRepository;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;

class ForumStatisticsService
{
    private ForumRepository $forumRepository;
    private ThreadRepository $threadRepository;
    private PostRepository $postRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ForumRepository $forumRepository,
        ThreadRepository $threadRepository,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->forumRepository = $forumRepository;
        $this->threadRepository = $threadRepository;
        $this->postRepository = $postRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Calcule les statistiques cumulées pour un forum et tous ses sous-forums
     */
    public function getForumStats(int $forumId): array
    {
        // Récupérer tous les IDs de sous-forums (récursivement)
        $conn = $this->entityManager->getConnection();
        $subforumsSql = "
            WITH RECURSIVE forum_tree AS (
                SELECT id FROM forum WHERE id = :forumId
                UNION ALL
                SELECT f.id FROM forum f
                JOIN forum_tree ft ON f.parent_id = ft.id
            )
            SELECT id FROM forum_tree
        ";
        
        $stmt = $conn->prepare($subforumsSql);
        $stmt->bindValue('forumId', $forumId);
        $result = $stmt->executeQuery();
        $forumIds = $result->fetchFirstColumn();
        
        if (empty($forumIds)) {
            return [
                'thread_count' => 0,
                'post_count' => 0,
                'subforum_count' => 0
            ];
        }
        
        // Statistiques des sous-forums directs
        $subforumCount = count($this->forumRepository->findBy(['parent' => $forumId]));
        
        // Compter le nombre total de discussions
        $threadCount = $this->threadRepository->countThreadsInForums($forumIds);
        
        // Compter le nombre total de posts
        $postCount = $this->postRepository->countPostsInForums($forumIds);
        
        return [
            'thread_count' => $threadCount,
            'post_count' => $postCount,
            'subforum_count' => $subforumCount,
            'forum_ids' => $forumIds
        ];
    }
}