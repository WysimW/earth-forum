<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Trouve le dernier post pour un forum spécifique, y compris dans tous ses sous-forums
     * (quelle que soit la profondeur de hiérarchie)
     */
    public function findLastPostInForum(int $forumId): ?Post
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Cette requête SQL récupère tous les IDs de forums sous le forum donné (à n'importe quel niveau)
        $subforumsSql = "
            WITH RECURSIVE forum_tree AS (
                SELECT id FROM forum WHERE id = :forumId
                UNION ALL
                SELECT f.id FROM forum f
                JOIN forum_tree ft ON f.parent_id = ft.id
            )
            SELECT id FROM forum_tree
        ";
        
        // Exécution de la requête pour obtenir tous les forums concernés
        $stmt = $conn->prepare($subforumsSql);
        $stmt->bindValue('forumId', $forumId);
        $result = $stmt->executeQuery();
        $forumIds = $result->fetchFirstColumn();

        if (empty($forumIds)) {
            return null;
        }
        
        // Maintenant, nous utilisons ces IDs pour trouver le dernier post
        $qb = $this->createQueryBuilder('p')
            ->join('p.thread', 't')
            ->join('t.forum', 'f')
            ->where('f.id IN (:forumIds)')
            ->setParameter('forumIds', $forumIds)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve le dernier post pour un thread spécifique
     */
    public function findLastPostForThread(int $threadId): ?Post
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.thread = :threadId')
            ->setParameter('threadId', $threadId)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve les personnages distincts participant à un thread
     */
    public function findDistinctCharactersByThread(int $threadId): array
    {
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQuery(
            'SELECT DISTINCT c 
             FROM App\Entity\Character c
             JOIN App\Entity\Post p WITH p.character = c
             WHERE p.thread = :threadId'
        )->setParameter('threadId', $threadId);
        
        return $query->getResult();
    }

    /**
     * Compte le nombre de posts dans un thread
     */
    public function countPostsInThread(int $threadId): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.thread', 't')
            ->where('t.id = :threadId')
            ->setParameter('threadId', $threadId)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    /**
     * Compte le nombre de posts dans une liste de forums
     */
    public function countPostsInForums(array $forumIds): int
    {
        if (empty($forumIds)) {
            return 0;
        }

        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.thread', 't')
            ->where('t.forum IN (:forumIds)')
            ->setParameter('forumIds', $forumIds);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
