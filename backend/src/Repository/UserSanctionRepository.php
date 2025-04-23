<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSanction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

/**
 * @extends ServiceEntityRepository<UserSanction>
 *
 * @method UserSanction|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSanction|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSanction[]    findAll()
 * @method UserSanction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSanctionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSanction::class);
    }

    /**
     * Vérifie si un utilisateur a une sanction active d'un type spécifique
     */
    public function hasActiveSanction(User $user, string $type): bool
    {
        $count = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.type = :type')
            ->andWhere('s.isActive = :active')
            ->andWhere('(s.expiresAt IS NULL OR s.expiresAt > :now)')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
        
        return $count > 0;
    }

    /**
     * Trouve toutes les sanctions actives d'un utilisateur
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.isActive = :active')
            ->andWhere('(s.expiresAt IS NULL OR s.expiresAt > :now)')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve l'historique des sanctions d'un utilisateur (sanctions expirées ou révoquées)
     */
    public function findHistoryByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('(s.isActive = :inactive OR s.expiresAt <= :now OR s.revokedAt IS NOT NULL)')
            ->setParameter('user', $user)
            ->setParameter('inactive', false)
            ->setParameter('now', new \DateTime())
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve la sanction active la plus restrictive pour un utilisateur
     * L'ordre de restriction est: FULL_BAN > BAN_MESSAGING > BAN_POSTING > MUTE > WARNING
     */
    public function findMostRestrictiveActiveSanction(User $user): ?UserSanction
    {
        // Ordre de priorité des sanctions
        $typeOrder = [
            UserSanction::TYPE_FULL_BAN => 1,
            UserSanction::TYPE_BAN_MESSAGING => 2,
            UserSanction::TYPE_BAN_POSTING => 3,
            UserSanction::TYPE_MUTE => 4,
            UserSanction::TYPE_WARNING => 5
        ];
        
        $activeSanctions = $this->findActiveByUser($user);
        
        if (empty($activeSanctions)) {
            return null;
        }
        
        // Trier les sanctions par ordre de restriction
        usort($activeSanctions, function($a, $b) use ($typeOrder) {
            return $typeOrder[$a->getType()] <=> $typeOrder[$b->getType()];
        });
        
        // Retourner la sanction la plus restrictive
        return $activeSanctions[0];
    }

    /**
     * Trouve les sanctions selon des filtres spécifiques pour l'admin
     */
    public function findByFilters(?string $status = 'active', ?string $type = null, ?int $userId = null, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.moderator', 'm')
            ->addSelect('u')
            ->addSelect('m');
        
        // Filtrage par statut
        switch ($status) {
            case 'active':
                $qb->andWhere('s.isActive = :active')
                   ->andWhere('(s.expiresAt IS NULL OR s.expiresAt > :now)')
                   ->setParameter('active', true)
                   ->setParameter('now', new \DateTime());
                break;
            case 'expired':
                $qb->andWhere('s.isActive = :active')
                   ->andWhere('s.expiresAt <= :now')
                   ->setParameter('active', true)
                   ->setParameter('now', new \DateTime());
                break;
            case 'revoked':
                $qb->andWhere('s.revokedAt IS NOT NULL');
                break;
            case 'inactive':
                $qb->andWhere('s.isActive = :inactive')
                   ->setParameter('inactive', false);
                break;
            // 'all' ne nécessite pas de filtres supplémentaires
        }
        
        // Filtrage par type
        if ($type) {
            $qb->andWhere('s.type = :type')
               ->setParameter('type', $type);
        }
        
        // Filtrage par utilisateur
        if ($userId) {
            $qb->andWhere('s.user = :userId')
               ->setParameter('userId', $userId);
        }
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $qb->orderBy('s.createdAt', 'DESC')
           ->setFirstResult($offset)
           ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de sanctions selon des filtres
     */
    public function countByFilters(?string $status = 'active', ?string $type = null, ?int $userId = null): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)');
        
        // Filtrage par statut
        switch ($status) {
            case 'active':
                $qb->andWhere('s.isActive = :active')
                   ->andWhere('(s.expiresAt IS NULL OR s.expiresAt > :now)')
                   ->setParameter('active', true)
                   ->setParameter('now', new \DateTime());
                break;
            case 'expired':
                $qb->andWhere('s.isActive = :active')
                   ->andWhere('s.expiresAt <= :now')
                   ->setParameter('active', true)
                   ->setParameter('now', new \DateTime());
                break;
            case 'revoked':
                $qb->andWhere('s.revokedAt IS NOT NULL');
                break;
            case 'inactive':
                $qb->andWhere('s.isActive = :inactive')
                   ->setParameter('inactive', false);
                break;
            // 'all' ne nécessite pas de filtres supplémentaires
        }
        
        // Filtrage par type
        if ($type) {
            $qb->andWhere('s.type = :type')
               ->setParameter('type', $type);
        }
        
        // Filtrage par utilisateur
        if ($userId) {
            $qb->andWhere('s.user = :userId')
               ->setParameter('userId', $userId);
        }
        
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve une sanction active par type pour un utilisateur
     */
    public function findActiveSanctionByType(User $user, string $type): ?UserSanction
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.type = :type')
            ->andWhere('s.isActive = :active')
            ->andWhere('(s.expiresAt IS NULL OR s.expiresAt > :now)')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les sanctions actives mais expirées
     */
    public function findExpiredActiveSanctions(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->andWhere('s.expiresAt IS NOT NULL')
            ->andWhere('s.expiresAt <= :now')
            ->andWhere('s.revokedAt IS NULL')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
} 