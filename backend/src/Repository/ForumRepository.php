<?php

namespace App\Repository;

use App\Entity\Forum;
use App\Entity\Thread;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Forum>
 */
class ForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forum::class);
    }

    public function findLatestThreadByRecentPostInForum(int $forumId): ?Thread
    {
        // Utiliser une sous-requête CTE récursive pour obtenir tous les forums concernés
        $conn = $this->getEntityManager()->getConnection();
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
            return null;
        }
        
        // Utiliser le QueryBuilder pour obtenir directement le thread avec le post le plus récent
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        return $qb->select('t')
            ->from('App\Entity\Thread', 't')
            ->join('t.posts', 'p')
            ->join('t.forum', 'f')
            ->where('f.id IN (:forumIds)')
            ->setParameter('forumIds', $forumIds)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countThreadsAndPostsInForum(int $forumId): array
    {
        // Utiliser une sous-requête CTE récursive pour obtenir tous les forums concernés
        $conn = $this->getEntityManager()->getConnection();
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
                'totalThreads' => 0,
                'totalPosts' => 0,
            ];
        }
        
        // Compter les threads en une seule requête
        $qbThreads = $this->getEntityManager()->createQueryBuilder();
        $totalThreads = $qbThreads->select('COUNT(t.id)')
            ->from('App\Entity\Thread', 't')
            ->where('t.forum IN (:forumIds)')
            ->setParameter('forumIds', $forumIds)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Compter les posts en une seule requête
        $qbPosts = $this->getEntityManager()->createQueryBuilder();
        $totalPosts = $qbPosts->select('COUNT(p.id)')
            ->from('App\Entity\Post', 'p')
            ->join('p.thread', 't')
            ->where('t.forum IN (:forumIds)')
            ->setParameter('forumIds', $forumIds)
            ->getQuery()
            ->getSingleScalarResult();
        
        return [
            'totalThreads' => $totalThreads,
            'totalPosts' => $totalPosts,
        ];
    }

    public function isForumOrParentInCategoryType(Forum $forum, string $categoryType): bool
    {
        // Start with the current forum
        while ($forum !== null) {
            $category = $forum->getCategory();

            // If the forum has a category and that category's type matches the specified type
            if ($category && $category->getType() && $category->getType()->getName() === $categoryType) {
                return true;
            }

            // Move to the parent forum if it exists
            $forum = $forum->getParent();
        }

        // If we reach here, neither the forum nor any of its parents match the specified category type
        return false;
    }

    public function findLatestThreads(int $forumId): array
    {
        $forum = $this->find($forumId);
        if (!$forum) {
            throw new \Exception('Forum not found');
        }

        $threads = [];

        // Get threads from the forum and its subforums
        $this->gatherThreads($forum, $threads);

        // Sort threads by creation date
        usort($threads, function (Thread $a, Thread $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        // Return only the latest 5 threads
        return array_slice($threads, 0, 5);
    }

    private function gatherThreads(Forum $forum, array &$threads)
    {
        // Add forum's threads to the list
        foreach ($forum->getThreads() as $thread) {
            $threads[] = $thread;
        }

        // Recursively add subforum threads
        foreach ($forum->getSubforums() as $subforum) {
            $this->gatherThreads($subforum, $threads);
        }
    }


    //    /**
    //     * @return Forum[] Returns an array of Forum objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Forum
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
