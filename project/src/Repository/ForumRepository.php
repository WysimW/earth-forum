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
        $forum = $this->find($forumId);
        if (!$forum) {
            throw new \Exception('Forum not found');
        }

        $latestThread = null;
        $latestPostDate = null;

        // Gather the forum and its subforums
        $forumsToCheck = [$forum];
        $forumsToCheck = array_merge($forumsToCheck, $forum->getSubforums()->toArray());

        foreach ($forumsToCheck as $subForum) {
            foreach ($subForum->getThreads() as $thread) {
                foreach ($thread->getPosts() as $post) {
                    if ($latestPostDate === null || $post->getCreatedAt() > $latestPostDate) {
                        $latestPostDate = $post->getCreatedAt();
                        $latestThread = $thread;
                    }
                }
            }
        }

        return $latestThread;
    }

    public function countThreadsAndPostsInForum(int $forumId): array
    {
        $forum = $this->find($forumId);
        if (!$forum) {
            throw new \Exception('Forum not found');
        }

        $totalThreads = 0;
        $totalPosts = 0;

        // Gather the forum and its subforums
        $forumsToCheck = [$forum];
        $forumsToCheck = array_merge($forumsToCheck, $forum->getSubforums()->toArray());

        foreach ($forumsToCheck as $subForum) {
            $threads = $subForum->getThreads();
            $totalThreads += count($threads);
            foreach ($threads as $thread) {
                $totalPosts += count($thread->getPosts());
            }
        }

        return [
            'totalThreads' => $totalThreads,
            'totalPosts' => $totalPosts,
        ];
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
