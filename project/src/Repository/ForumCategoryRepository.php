<?php

namespace App\Repository;

use App\Entity\ForumCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Thread;


/**
 * @extends ServiceEntityRepository<ForumCategory>
 */
class ForumCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumCategory::class);
    }

    public function findLatestThreadByRecentPostInCategory(int $categoryId): ?Thread
    {
        $category = $this->find($categoryId);
        if (!$category) {
            throw new \Exception('Category not found');
        }
    
        $latestThread = null;
        $latestPostDate = null;
    
        foreach ($category->getForums() as $forum) {
            // Include both the forum and its subforums
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
        }
    
        return $latestThread;
    }
    
    
    
    
    

    //    /**
    //     * @return ForumCategory[] Returns an array of ForumCategory objects
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

    //    public function findOneBySomeField($value): ?ForumCategory
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
