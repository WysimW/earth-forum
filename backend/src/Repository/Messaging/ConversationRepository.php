<?php

namespace App\Repository\Messaging;

use App\Entity\Messaging\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 *
 * @method Conversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Trouve toutes les conversations d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->andWhere('p.isActive = :active')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->setParameter('archived', false)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une conversation privée entre deux utilisateurs
     */
    public function findPrivateConversation(User $user1, User $user2): ?Conversation
    {
        $qb = $this->createQueryBuilder('c');
        
        return $qb->join('c.participants', 'p1')
            ->join('c.participants', 'p2')
            ->where('c.type = :type')
            ->andWhere('p1.user = :user1')
            ->andWhere('p2.user = :user2')
            ->andWhere('p1.isActive = :active')
            ->andWhere('p2.isActive = :active')
            ->setParameter('type', Conversation::TYPE_PRIVATE)
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les conversations publiques accessibles à un utilisateur
     */
    public function findPublicConversations(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.type = :type')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('type', Conversation::TYPE_PUBLIC)
            ->setParameter('archived', false)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les conversations avec des messages non lus
     */
    public function findWithUnreadMessages(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->join('c.messages', 'm')
            ->where('p.user = :user')
            ->andWhere('p.isActive = :active')
            ->andWhere('m.createdAt > p.lastReadAt OR p.lastReadAt IS NULL')
            ->andWhere('m.author != :user')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }
} 