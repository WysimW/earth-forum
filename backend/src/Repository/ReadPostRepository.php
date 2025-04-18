<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\ReadPost;
use App\Entity\Thread;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadPost>
 *
 * @method ReadPost|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReadPost|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReadPost[]    findAll()
 * @method ReadPost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReadPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadPost::class);
    }

    public function findByUserAndPost(User $user, Post $post): ?ReadPost
    {
        return $this->findOneBy([
            'user' => $user,
            'post' => $post
        ]);
    }

    /**
     * Récupère tous les posts non lus par un utilisateur
     * Exclut les messages des threads où l'utilisateur participe et les messages des threads épinglés
     * qui sont affichés dans leurs propres catégories
     */
    public function findUnreadPostsForUser(User $user): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        // Sous-requête pour récupérer les IDs des threads où l'utilisateur a des personnages participants
        $participatingThreadsSubQuery1 = $this->getEntityManager()->createQueryBuilder();
        $participatingThreadsSubQuery1->select('DISTINCT thread_sub.id')
            ->from('App\Entity\Thread', 'thread_sub')
            ->leftJoin('thread_sub.participants', 'c')
            ->where('c.user = :user');
            
        // Sous-requête pour récupérer les IDs des threads où l'utilisateur a déjà posté
        $participatingThreadsSubQuery2 = $this->getEntityManager()->createQueryBuilder();
        $participatingThreadsSubQuery2->select('DISTINCT thread_post.id')
            ->from('App\Entity\Thread', 'thread_post')
            ->join('thread_post.posts', 'thread_posts')
            ->where('thread_posts.author = :user');
            
        // Récupérer les messages non lus, en excluant ceux des threads épinglés et ceux des threads participatifs
        return $qb->select('p')
            ->from('App\Entity\Post', 'p')
            ->join('p.thread', 't')
            ->leftJoin('App\Entity\ReadPost', 'rp', 'WITH', 'rp.post = p.id AND rp.user = :user')
            ->where('rp.id IS NULL')
            ->andWhere('p.isDraft = false')
            ->andWhere('t.sticky = false') // Exclure les threads épinglés
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->orX(
                        $qb->expr()->in('t.id', $participatingThreadsSubQuery1->getDQL()),
                        $qb->expr()->in('t.id', $participatingThreadsSubQuery2->getDQL())
                    )
                )
            ) // Exclure les threads participatifs
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Récupère tous les posts non lus par un utilisateur dans les threads où il participe
     * Un utilisateur participe à un thread si :
     * - L'un de ses personnages est dans la liste des participants
     * - OU il a déjà écrit un message dans ce thread
     */
    public function findUnreadPostsInParticipatingThreads(User $user): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        // Sous-requête pour récupérer les IDs des threads où l'utilisateur a des personnages participants
        $subQuery1 = $this->getEntityManager()->createQueryBuilder();
        $subQuery1->select('DISTINCT thread_sub.id')
            ->from('App\Entity\Thread', 'thread_sub')
            ->leftJoin('thread_sub.participants', 'c')
            ->where('c.user = :user');
            
        // Sous-requête pour récupérer les IDs des threads où l'utilisateur a déjà posté
        $subQuery2 = $this->getEntityManager()->createQueryBuilder();
        $subQuery2->select('DISTINCT thread_post.id')
            ->from('App\Entity\Thread', 'thread_post')
            ->join('thread_post.posts', 'thread_posts')
            ->where('thread_posts.author = :user');
            
        // Combiner les deux sous-requêtes
        return $qb->select('p')
            ->from('App\Entity\Post', 'p')
            ->join('p.thread', 't')
            ->leftJoin('App\Entity\ReadPost', 'rp', 'WITH', 'rp.post = p.id AND rp.user = :user')
            ->where('rp.id IS NULL')
            ->andWhere('p.isDraft = false')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('t.id', $subQuery1->getDQL()),
                    $qb->expr()->in('t.id', $subQuery2->getDQL())
                )
            )
            ->andWhere('p.author != :user') // Exclure les messages de l'utilisateur lui-même
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Récupère tous les posts non lus dans des threads sticky (épinglés)
     */
    public function findUnreadAdminPostsForUser(User $user): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        return $qb->select('p')
            ->from('App\Entity\Post', 'p')
            ->join('p.thread', 't')
            ->leftJoin('App\Entity\ReadPost', 'rp', 'WITH', 'rp.post = p.id AND rp.user = :user')
            ->where('rp.id IS NULL')
            ->andWhere('p.isDraft = false')
            ->andWhere('t.sticky = true')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    public function markAsRead(User $user, Post $post): void
    {
        $entityManager = $this->getEntityManager();
        
        // Vérifie si le post est déjà marqué comme lu
        $readPost = $this->findByUserAndPost($user, $post);
        
        if (!$readPost) {
            $readPost = new ReadPost();
            $readPost->setUser($user);
            $readPost->setPost($post);
            
            $entityManager->persist($readPost);
            $entityManager->flush();
        }
    }
    
    public function markThreadAsRead(User $user, Thread $thread): void
    {
        $entityManager = $this->getEntityManager();
        
        // Récupère tous les posts du thread
        $posts = $thread->getPosts();
        
        foreach ($posts as $post) {
            // Vérifie si le post est déjà marqué comme lu
            $readPost = $this->findByUserAndPost($user, $post);
            
            if (!$readPost) {
                $readPost = new ReadPost();
                $readPost->setUser($user);
                $readPost->setPost($post);
                
                $entityManager->persist($readPost);
            }
        }
        
        $entityManager->flush();
    }
} 