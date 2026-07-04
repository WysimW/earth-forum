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
     * @param int[] $threadIds
     * @return int[]
     */
    public function findParticipatingThreadIdsForUser(User $user, array $threadIds): array
    {
        $threadIds = array_values(array_unique(array_filter(array_map('intval', $threadIds))));
        if ($threadIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('t')
            ->select('DISTINCT t.id AS id')
            ->leftJoin('t.characterCreator', 'cc')
            ->leftJoin('t.participants', 'participantCharacter')
            ->leftJoin('participantCharacter.user', 'participantUser')
            ->leftJoin('t.posts', 'threadPost')
            ->leftJoin('threadPost.author', 'threadPostAuthor')
            ->leftJoin('threadPost.character', 'threadPostCharacter')
            ->leftJoin('threadPostCharacter.user', 'threadPostCharacterUser')
            ->where('t.id IN (:threadIds)')
            ->andWhere('(t.author = :user OR cc.user = :user OR participantUser = :user OR threadPostAuthor = :user OR threadPostCharacterUser = :user)')
            ->setParameter('threadIds', $threadIds)
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
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

    /**
     * Threads créés par un utilisateur (RP + HRP), avec filtres optionnels.
     *
     * @return Thread[]
     */
    public function findThreadsCreatedByUser(
        int $userId,
        ?string $threadType = null,
        ?string $status = null,
        ?int $universeId = null,
        ?int $limit = null,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.author = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.updatedAt', 'DESC');

        $this->applyThreadTypeFilter($qb, $threadType);
        $this->applyStatusFilter($qb, $status);
        $this->applyUniverseFilter($qb, $universeId);

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Threads où l'utilisateur participe (RP via personnages + HRP via posts utilisateur).
     *
     * @return Thread[]
     */
    public function findThreadsWithUserParticipationAllTypes(
        int $userId,
        ?string $threadType = null,
        ?string $status = null,
        ?int $universeId = null,
        ?int $limit = null,
    ): array {
        $rpThreads = [];
        $hrpThreads = [];

        if ($threadType === null || $threadType === 'all' || $threadType === 'roleplay') {
            $rpThreads = $this->findAllThreadsWithUserParticipation($userId, $status !== 'all' ? $status : null);
            if ($universeId !== null) {
                $rpThreads = $this->filterThreadsByUniverse($rpThreads, $universeId);
            }
        }

        if ($threadType === null || $threadType === 'all' || $threadType === 'hrp') {
            $hrpThreads = $this->findHrpThreadsWithUserParticipation($userId, $status, $universeId);
        }

        $merged = $this->mergeThreadsById($rpThreads, $hrpThreads);

        if ($limit !== null) {
            return array_slice($merged, 0, $limit);
        }

        return $merged;
    }

    /**
     * Compteurs agrégés pour les threads créés par un utilisateur.
     *
     * @return array{total: int, open: int, closed: int, archived: int, roleplay: int, hrp: int}
     */
    public function countThreadsCreatedByUser(int $userId, ?int $universeId = null): array
    {
        $threads = $this->findThreadsCreatedByUser($userId, null, null, $universeId);

        return $this->buildThreadStats($threads);
    }

    /**
     * Compteurs agrégés pour les participations utilisateur.
     *
     * @return array{total: int, open: int, closed: int, archived: int, roleplay: int, hrp: int}
     */
    public function countThreadsParticipatingByUser(int $userId, ?int $universeId = null): array
    {
        $threads = $this->findThreadsWithUserParticipationAllTypes($userId, null, null, $universeId);

        return $this->buildThreadStats($threads);
    }

    /**
     * @return Thread[]
     */
    private function findHrpThreadsWithUserParticipation(
        int $userId,
        ?string $status = null,
        ?int $universeId = null,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->distinct()
            ->join('t.posts', 'po')
            ->andWhere('po.author = :userId')
            ->andWhere('t.type != :roleplayType')
            ->setParameter('userId', $userId)
            ->setParameter('roleplayType', 'roleplay')
            ->orderBy('t.updatedAt', 'DESC');

        $this->applyStatusFilter($qb, $status);
        $this->applyUniverseFilter($qb, $universeId);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Thread[] $threads
     *
     * @return array{total: int, open: int, closed: int, archived: int, roleplay: int, hrp: int}
     */
    private function buildThreadStats(array $threads): array
    {
        $stats = [
            'total' => count($threads),
            'open' => 0,
            'closed' => 0,
            'archived' => 0,
            'roleplay' => 0,
            'hrp' => 0,
        ];

        foreach ($threads as $thread) {
            $threadStatus = $thread->getStatus();
            if (isset($stats[$threadStatus])) {
                $stats[$threadStatus]++;
            }

            if ($thread->getType() === 'roleplay') {
                $stats['roleplay']++;
            } else {
                $stats['hrp']++;
            }
        }

        return $stats;
    }

    /**
     * @param Thread[] ...$threadLists
     *
     * @return Thread[]
     */
    private function mergeThreadsById(array ...$threadLists): array
    {
        $byId = [];

        foreach ($threadLists as $list) {
            foreach ($list as $thread) {
                $byId[$thread->getId()] = $thread;
            }
        }

        $all = array_values($byId);
        usort($all, static fn (Thread $a, Thread $b): int => $b->getUpdatedAt() <=> $a->getUpdatedAt());

        return $all;
    }

    /**
     * @param Thread[] $threads
     *
     * @return Thread[]
     */
    private function filterThreadsByUniverse(array $threads, int $universeId): array
    {
        return array_values(array_filter(
            $threads,
            static fn (Thread $thread): bool => self::resolveThreadUniverseId($thread) === $universeId
        ));
    }

    private static function resolveThreadUniverseId(Thread $thread): ?int
    {
        if ($thread->getUniverse() !== null) {
            return $thread->getUniverse()->getId();
        }

        $forum = $thread->getForum();
        if ($forum === null) {
            return null;
        }

        if ($forum->getUniverse() !== null) {
            return $forum->getUniverse()->getId();
        }

        $elseworld = $forum->getElseworld();
        if ($elseworld !== null && $elseworld->getParentUniverse() !== null) {
            return $elseworld->getParentUniverse()->getId();
        }

        return null;
    }

    private function applyThreadTypeFilter(\Doctrine\ORM\QueryBuilder $qb, ?string $threadType): void
    {
        if ($threadType === null || $threadType === 'all') {
            return;
        }

        if ($threadType === 'roleplay') {
            $qb->andWhere('t.type = :threadType')->setParameter('threadType', 'roleplay');

            return;
        }

        if ($threadType === 'hrp') {
            $qb->andWhere('t.type != :threadType')->setParameter('threadType', 'roleplay');
        }
    }

    private function applyStatusFilter(\Doctrine\ORM\QueryBuilder $qb, ?string $status): void
    {
        if ($status === null || $status === 'all') {
            return;
        }

        $qb->andWhere('t.status = :status')->setParameter('status', $status);
    }

    private function applyUniverseFilter(\Doctrine\ORM\QueryBuilder $qb, ?int $universeId): void
    {
        if ($universeId === null) {
            return;
        }

        $qb->join('t.forum', 'dashForum')
            ->leftJoin('dashForum.universe', 'dashFu')
            ->leftJoin('dashForum.elseworld', 'dashFe')
            ->leftJoin('dashFe.parentUniverse', 'dashFeu')
            ->leftJoin('t.universe', 'dashTu')
            ->andWhere('(dashFu.id = :universeId OR dashFeu.id = :universeId OR dashTu.id = :universeId)')
            ->setParameter('universeId', $universeId);
    }
}
