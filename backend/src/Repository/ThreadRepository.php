<?php

namespace App\Repository;

use App\Entity\Thread;
use App\Entity\User;
use App\Entity\Univers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Thread>
 */
class ThreadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Thread::class);
    }

    /**
     * Trouve tous les threads dans lesquels un personnage participe
     *
     * @param int $characterId L'ID du personnage
     * @return Thread[] Returns an array of Thread objects
     */
    public function findByParticipantId(int $characterId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.participants', 'p')
            ->andWhere('p.id = :characterId')
            ->setParameter('characterId', $characterId)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les threads récents où un utilisateur participe avec ses personnages
     */
    public function findRecentThreadsWithUserParticipation(int $userId, int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.participants', 'p')
            ->join('p.user', 'u')
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les threads où un utilisateur participe avec ses personnages
     */
    public function findThreadsWithUserParticipation(int $userId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.participants', 'p')
            ->join('p.user', 'u')
            ->andWhere('u.id = :userId')
            ->andWhere('t.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', 'roleplay');

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('t.updatedAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    /**
     * Trouve les threads où un utilisateur participe avec ses personnages, filtrés par univers
     */
    public function findThreadsWithUserParticipationByUniverse(int $userId, int $universeId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.participants', 'p')
            ->join('p.user', 'u')
            ->join('t.forum', 'f')
            ->leftJoin('f.universe', 'fu')
            ->leftJoin('f.elseworld', 'fe')
            ->leftJoin('fe.parentUniverse', 'feu')
            ->andWhere('u.id = :userId')
            ->andWhere('t.type = :type')
            ->andWhere('(fu.id = :universeId OR feu.id = :universeId)')
            ->setParameter('userId', $userId)
            ->setParameter('universeId', $universeId)
            ->setParameter('type', 'roleplay');

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('t.updatedAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    /**
     * Trouve les threads récemment actifs
     */
    public function findRecentActiveThreads(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->andWhere('t.type = :type')
            ->setParameter('status', 'open')
            ->setParameter('type', 'roleplay')
            ->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les threads actifs
     */
    public function findActiveThreads(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->andWhere('t.type = :type')
            ->leftJoin('t.posts', 'p')
            ->groupBy('t.id')
            ->having('COUNT(p.id) < 20') // Limiter aux scènes qui ont moins de 20 messages
            ->setParameter('status', 'open')
            ->setParameter('type', 'roleplay')
            ->orderBy('t.updatedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les threads actifs filtrés par univers
     */
    public function findActiveThreadsByUniverse(int $universeId, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->andWhere('t.type = :type')
            ->leftJoin('t.posts', 'p')
            ->join('t.forum', 'f')
            ->leftJoin('f.universe', 'fu')
            ->leftJoin('f.elseworld', 'fe')
            ->leftJoin('fe.parentUniverse', 'feu')
            ->andWhere('(fu.id = :universeId OR feu.id = :universeId)')
            ->groupBy('t.id')
            ->having('COUNT(p.id) < 20') // Limiter aux scènes qui ont moins de 20 messages
            ->setParameter('status', 'open')
            ->setParameter('type', 'roleplay')
            ->setParameter('universeId', $universeId)
            ->orderBy('t.updatedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve tous les threads de type roleplay liés à un univers
     */
    public function findRoleplayThreadsByUniverse(Univers $univers): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->join('t.forum', 'f')
            ->leftJoin('f.universe', 'fu')
            ->leftJoin('f.elseworld', 'fe')
            ->leftJoin('fe.parentUniverse', 'feu')
            ->andWhere('(fu = :univers OR feu = :univers)')
            ->setParameter('type', 'roleplay')
            ->setParameter('univers', $univers)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les threads récents filtrés par univers
     */
    public function findRecentThreadsByUniverse(int $universeId, ?string $status = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->join('t.forum', 'f')
            ->leftJoin('f.universe', 'fu')
            ->leftJoin('f.elseworld', 'fe')
            ->leftJoin('fe.parentUniverse', 'feu')
            ->andWhere('(fu.id = :universeId OR feu.id = :universeId)')
            ->setParameter('type', 'roleplay')
            ->setParameter('universeId', $universeId)
            ->orderBy('t.updatedAt', 'DESC');

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve tous les threads d'un univers avec filtre de statut
     */
    public function findThreadsByUniverse(int $universeId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->join('t.forum', 'f')
            ->leftJoin('f.universe', 'fu')
            ->leftJoin('f.elseworld', 'fe')
            ->leftJoin('fe.parentUniverse', 'feu')
            ->andWhere('(fu.id = :universeId OR feu.id = :universeId)')
            ->setParameter('type', 'roleplay')
            ->setParameter('universeId', $universeId)
            ->orderBy('t.updatedAt', 'DESC');

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les threads créés par un utilisateur dans un univers spécifique
     */
    public function findThreadsByAuthorAndUniverse(int $userId, int $universeId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->andWhere('t.author = :userId')
            ->join('t.forum', 'f')
            ->leftJoin('f.universe', 'fu')
            ->leftJoin('f.elseworld', 'fe')
            ->leftJoin('fe.parentUniverse', 'feu')
            ->andWhere('(fu.id = :universeId OR feu.id = :universeId)')
            ->setParameter('type', 'roleplay')
            ->setParameter('userId', $userId)
            ->setParameter('universeId', $universeId)
            ->orderBy('t.updatedAt', 'DESC');

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche des threads en fonction de critères
     */
    public function searchThreads(?string $keyword = null, ?string $status = null, ?string $forumType = null): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($keyword) {
            $qb->andWhere('t.title LIKE :keyword OR t.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if ($forumType) {
            $qb->join('t.forum', 'f')
               ->andWhere('f.isRoleplay = :isRoleplay')
               ->setParameter('isRoleplay', $forumType === 'roleplay');
        }

        return $qb->orderBy('t.updatedAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    /**
     * Trouve toutes les fiches de personnage créées par un utilisateur (via l'auteur du thread ou les threads liés aux personnages de l'utilisateur)
     */
    public function findCharacterSheetsByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.characterSheet', 'c')
            ->where('t.type = :type')
            ->andWhere('c.user = :user')
            ->setParameter('type', 'character_sheet')
            ->setParameter('user', $user)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les fiches de personnage d'un utilisateur dans un univers spécifique
     */
    public function findCharacterSheetsByUserAndUniverse(User $user, Univers $univers): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.characterSheet', 'c')
            ->join('t.forum', 'f')
            ->leftJoin('f.universe', 'fu')
            ->leftJoin('f.elseworld', 'fe')
            ->leftJoin('fe.parentUniverse', 'feu')
            ->where('t.type = :type')
            ->andWhere('c.user = :user')
            ->andWhere('(fu = :univers OR feu = :univers)')
            ->setParameter('type', 'character_sheet')
            ->setParameter('user', $user)
            ->setParameter('univers', $univers)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les fiches de personnage
     */
    public function findAllCharacterSheets(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.type = :type')
            ->setParameter('type', 'character_sheet')
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les fiches de personnage par statut
     */
    public function findCharacterSheetsByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.characterSheet', 'c')
            ->where('t.type = :type')
            ->andWhere('c.status = :status')
            ->setParameter('type', 'character_sheet')
            ->setParameter('status', $status)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de threads dans une liste de forums
     */
    public function countThreadsInForums(array $forumIds): int
    {
        if (empty($forumIds)) {
            return 0;
        }

        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.forum IN (:forumIds)')
            ->setParameter('forumIds', $forumIds);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
