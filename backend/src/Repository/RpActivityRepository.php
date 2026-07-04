<?php

namespace App\Repository;

use App\Entity\RpActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RpActivity>
 */
class RpActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RpActivity::class);
    }

    /**
     * @return RpActivity[]
     */
    public function findForUniverseSlug(string $universeSlug, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.universe', 'u')
            ->leftJoin('a.faction', 'f')
            ->leftJoin('a.threads', 't')
            ->addSelect('u', 'f', 't')
            ->andWhere('u.slug = :slug')
            ->setParameter('slug', $universeSlug)
            ->orderBy('a.reminderAt', 'ASC')
            ->addOrderBy('a.updatedAt', 'DESC');

        if (!empty($filters['kind'])) {
            $qb->andWhere('a.kind = :kind')->setParameter('kind', $filters['kind']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('a.status = :status')->setParameter('status', $filters['status']);
        }

        if (!empty($filters['factionId'])) {
            $qb->andWhere('f.id = :factionId')->setParameter('factionId', (int) $filters['factionId']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return RpActivity[]
     */
    public function findDueReminders(\DateTimeImmutable $referenceDate): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.reminderAt IS NOT NULL')
            ->andWhere('a.reminderAt <= :now')
            ->andWhere('a.reminderSentAt IS NULL')
            ->andWhere('a.status = :status')
            ->setParameter('now', $referenceDate)
            ->setParameter('status', RpActivity::STATUS_OPEN)
            ->orderBy('a.reminderAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
