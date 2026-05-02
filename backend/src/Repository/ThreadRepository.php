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
            ->distinct()
            ->leftJoin('t.participants', 'p')
            ->leftJoin('p.user', 'pu')
            ->leftJoin('t.posts', 'po')
            ->leftJoin('po.character', 'pc')
            ->leftJoin('pc.user', 'pcu')
            ->andWhere('t.type = :type')
            ->andWhere('(pu.id = :userId OR pcu.id = :userId)')
            ->setParameter('userId', $userId)
            ->setParameter('type', 'roleplay')
            ->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve TOUS les threads où un utilisateur participe avec ses personnages (toutes univers confondus)
     */
    public function findAllThreadsWithUserParticipation(int $userId, ?string $status = null): array
    {
        // Première requête : threads où l'utilisateur a des personnages participants
        $qb1 = $this->createQueryBuilder('t')
            ->join('t.participants', 'p')
            ->join('p.user', 'u')
            ->andWhere('u.id = :userId')
            ->andWhere('t.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', 'roleplay');

        if ($status) {
            $qb1->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        $threadsWithParticipants = $qb1->getQuery()->getResult();

        // Deuxième requête : threads où l'utilisateur a posté avec ses personnages
        $qb2 = $this->createQueryBuilder('t')
            ->join('t.posts', 'po')
            ->join('po.character', 'pc')
            ->join('pc.user', 'u')
            ->andWhere('u.id = :userId')
            ->andWhere('t.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', 'roleplay');

        if ($status) {
            $qb2->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        $threadsWithPosts = $qb2->getQuery()->getResult();

        // Fusionner les résultats sans doublons
        $allThreadsById = [];
        
        foreach ($threadsWithParticipants as $thread) {
            $allThreadsById[$thread->getId()] = $thread;
        }
        
        foreach ($threadsWithPosts as $thread) {
            $allThreadsById[$thread->getId()] = $thread;
        }
        
        $allThreads = array_values($allThreadsById);
        
        // Trier par date de mise à jour décroissante
        usort($allThreads, function($a, $b) {
            return $b->getUpdatedAt() <=> $a->getUpdatedAt();
        });

        return $allThreads;
    }

    /**
     * Trouve les threads où un utilisateur participe avec ses personnages
     */
    public function findThreadsWithUserParticipation(int $userId, ?string $status = null): array
    {
        // Utilise la même méthode que pour tous les threads
        return $this->findAllThreadsWithUserParticipation($userId, $status);
    }

    /**
     * Trouve tous les threads créés par un utilisateur
     */
    public function findThreadsByAuthor(int $userId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->andWhere('t.author = :userId')
            ->setParameter('type', 'roleplay')
            ->setParameter('userId', $userId)
            ->orderBy('t.updatedAt', 'DESC');

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les threads récents
     */
    public function findRecentThreads(?string $status = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->setParameter('type', 'roleplay')
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
     * Trouve tous les threads par statut
     */
    public function findAllThreadsByStatus(?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->setParameter('type', 'roleplay')
            ->orderBy('t.updatedAt', 'DESC');

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
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
     * Trouve les threads d'un forum avec pagination et filtres
     * 
     * @param int $forumId ID du forum
     * @param array $filters Filtres à appliquer ['type' => 'roleplay', 'status' => 'open', 'search' => 'keyword', 'author' => userId]
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @return array ['threads' => Thread[], 'total' => int, 'pages' => int]
     */
    public function findThreadsByForumWithPagination(int $forumId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.forum = :forumId')
            ->setParameter('forumId', $forumId);

        // Filtre par type
        if (isset($filters['type']) && $filters['type'] !== 'all') {
            $qb->andWhere('t.type = :type')
               ->setParameter('type', $filters['type']);
        }

        // Filtre par statut
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters['status']);
        }

        // Filtre par recherche (titre)
        if (isset($filters['search']) && !empty($filters['search'])) {
            $qb->andWhere('t.title LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filtre par auteur
        if (isset($filters['author']) && $filters['author']) {
            $qb->andWhere('t.author = :author')
               ->setParameter('author', $filters['author']);
        }

        // Filtre par personnage créateur (pour les threads RP)
        if (isset($filters['character']) && $filters['character']) {
            $qb->andWhere('t.characterCreator = :character')
               ->setParameter('character', $filters['character']);
        }

        // Filtre par participation utilisateur (threads où l'utilisateur a posté)
        $hasUserFilter = isset($filters['userId']) && $filters['userId'];
        if ($hasUserFilter) {
            $qb->leftJoin('t.posts', 'p')
               ->leftJoin('p.character', 'pc')
               ->andWhere('(p.author = :userId OR pc.user = :userId)')
               ->setParameter('userId', $filters['userId'])
               ->groupBy('t.id'); // Important pour éviter les doublons
        }

        // Compter le total avant pagination
        $totalQb = clone $qb;
        if ($hasUserFilter) {
            // Retirer le GROUP BY du clone pour le COUNT
            $totalQb->resetDQLPart('groupBy');
            $total = (int) $totalQb->select('COUNT(DISTINCT t.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } else {
            $total = (int) $totalQb->select('COUNT(t.id)')
                ->getQuery()
                ->getSingleScalarResult();
        }

        // Tri : sticky d'abord, puis par date de mise à jour
        $qb->orderBy('t.sticky', 'DESC')
           ->addOrderBy('t.updatedAt', 'DESC');

        // Pagination
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        $threads = $qb->getQuery()->getResult();
        $totalPages = (int) ceil($total / $limit);

        return [
            'threads' => $threads,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
        ];
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
