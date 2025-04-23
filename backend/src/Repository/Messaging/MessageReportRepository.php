<?php

namespace App\Repository\Messaging;

use App\Entity\Messaging\MessageReport;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageReport>
 */
class MessageReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageReport::class);
    }

    /**
     * Trouver les signalements en attente
     */
    public function findPendingReports(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', MessageReport::STATUS_PENDING)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les signalements en attente
     */
    public function countPendingReports(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :status')
            ->setParameter('status', MessageReport::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouver les signalements en attente pour une conversation
     */
    public function findPendingByConversation(int $conversationId): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.message', 'm')
            ->join('m.conversation', 'c')
            ->andWhere('c.id = :conversationId')
            ->andWhere('r.status = :status')
            ->setParameter('conversationId', $conversationId)
            ->setParameter('status', MessageReport::STATUS_PENDING)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifier si un message a déjà été signalé par un utilisateur
     */
    public function isMessageReportedByUser(int $messageId, User $user): bool
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.message = :messageId')
            ->andWhere('r.reporter = :userId')
            ->setParameter('messageId', $messageId)
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result > 0;
    }
} 