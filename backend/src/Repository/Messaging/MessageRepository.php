<?php

namespace App\Repository\Messaging;

use App\Entity\Messaging\Conversation;
use App\Entity\Messaging\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Trouve les messages d'une conversation avec pagination
     */
    public function findByConversation(Conversation $conversation, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Fenêtre de messages pour affichage type chat : les plus récents d'abord,
     * puis retour ordre chronologique croissant.
     *
     * @param int|null $beforeMessageId Si défini, charge les messages strictement plus anciens que cet id (scroll vers le haut).
     *
     * @return Message[]
     */
    public function findRecentMessagesForChat(Conversation $conversation, int $limit = 30, ?int $beforeMessageId = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($limit);

        if ($beforeMessageId !== null && $beforeMessageId > 0) {
            $qb->andWhere('m.id < :beforeId')
                ->setParameter('beforeId', $beforeMessageId);
        }

        $rows = $qb->getQuery()->getResult();

        return array_reverse($rows);
    }

    /**
     * Nombre de messages plus anciens que $beforeMessageId dans la conversation.
     */
    public function countMessagesOlderThan(Conversation $conversation, int $beforeMessageId): int
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('COUNT(m.id)')
            ->where('m.conversation = :conversation')
            ->andWhere('m.id < :beforeId')
            ->setParameter('conversation', $conversation)
            ->setParameter('beforeId', $beforeMessageId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findLastMessageInConversation(Conversation $conversation): ?Message
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les messages non lus dans une conversation pour un utilisateur
     */
    public function countUnreadByConversation(Conversation $conversation, User $user): int
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('COUNT(m.id)')
            ->where('m.conversation = :conversation')
            ->andWhere('m.author != :user')
            ->andWhere('m.isDeleted = :deleted')
            ->andWhere('m.createdAt > (
                SELECT COALESCE(MAX(p.lastReadAt), :old_date)
                FROM App\Entity\Messaging\ConversationParticipant p
                WHERE p.conversation = :conversation
                AND p.user = :user
            )')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->setParameter('deleted', false)
            ->setParameter('old_date', new \DateTime('2000-01-01'));
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Recherche des messages par contenu
     */
    public function searchByContent(string $query, User $user): array
    {
        $qb = $this->createQueryBuilder('m');
        return $qb->join('m.conversation', 'c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->andWhere('p.isActive = :active')
            ->andWhere('m.content LIKE :query')
            ->andWhere('m.isDeleted = :deleted')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('deleted', false)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages de roleplay dans une conversation
     */
    public function findRoleplayMessages(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->andWhere('m.isRoleplay = :isRoleplay')
            ->andWhere('m.isDeleted = :deleted')
            ->setParameter('conversation', $conversation)
            ->setParameter('isRoleplay', true)
            ->setParameter('deleted', false)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte tous les messages non lus dans toutes les conversations de l'utilisateur
     */
    public function countAllUnread(User $user): int
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('COUNT(m.id)')
            ->join('m.conversation', 'c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->andWhere('p.isActive = :active')
            ->andWhere('c.isArchived = :archived')
            ->andWhere('m.author != :user')
            ->andWhere('m.createdAt > COALESCE(p.lastReadAt, :old_date)')
            ->andWhere('m.isDeleted = :deleted')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->setParameter('archived', false)
            ->setParameter('old_date', new \DateTime('2000-01-01'))
            ->setParameter('deleted', false);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les messages plus récents qu'un ID spécifique dans une conversation
     */
    public function findMessagesNewerThan(Conversation $conversation, int $messageId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->andWhere('m.id > :messageId')
            ->setParameter('conversation', $conversation)
            ->setParameter('messageId', $messageId)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de messages dans une conversation
     */
    public function countByConversation(Conversation $conversation): int
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('COUNT(m.id)')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
} 