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
            $forum = $forum->getForum();
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
