<?php

namespace App\Repository\Messaging;

use App\Entity\Messaging\Conversation;
use App\Entity\Messaging\ConversationParticipant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConversationParticipant>
 *
 * @method ConversationParticipant|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConversationParticipant|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConversationParticipant[]    findAll()
 * @method ConversationParticipant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConversationParticipant::class);
    }

    /**
     * Trouve tous les participants actifs d'une conversation
     */
    public function findActiveByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.conversation = :conversation')
            ->andWhere('p.isActive = :active')
            ->setParameter('conversation', $conversation)
            ->setParameter('active', true)
            ->orderBy('p.role', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un participant spécifique dans une conversation
     */
    public function findOneByConversationAndUser(Conversation $conversation, User $user): ?ConversationParticipant
    {
        return $this->createQueryBuilder('p')
            ->where('p.conversation = :conversation')
            ->andWhere('p.user = :user')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les administrateurs d'une conversation
     */
    public function findAdminsByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.conversation = :conversation')
            ->andWhere('p.role = :role')
            ->andWhere('p.isActive = :active')
            ->setParameter('conversation', $conversation)
            ->setParameter('role', ConversationParticipant::ROLE_ADMIN)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les modérateurs d'une conversation
     */
    public function findModeratorsByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.conversation = :conversation')
            ->andWhere('p.role IN (:roles)')
            ->andWhere('p.isActive = :active')
            ->setParameter('conversation', $conversation)
            ->setParameter('roles', [ConversationParticipant::ROLE_ADMIN, ConversationParticipant::ROLE_MODERATOR])
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les conversations dont un utilisateur est membre
     */
    public function findConversationsByUser(User $user): array
    {
        $entityManager = $this->getEntityManager();
        
        // Créer un QueryBuilder sur l'entité Conversation directement
        return $entityManager->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Messaging\Conversation', 'c')
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
} 