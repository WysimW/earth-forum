<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\RpActivity;
use App\Entity\RpActivityRegistration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RpActivityRegistration>
 */
class RpActivityRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RpActivityRegistration::class);
    }

    public function findActiveByActivityAndCharacter(RpActivity $activity, Character $character): ?RpActivityRegistration
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.activity = :activity')
            ->andWhere('r.character = :character')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('activity', $activity)
            ->setParameter('character', $character)
            ->setParameter('statuses', [
                RpActivityRegistration::STATUS_PENDING,
                RpActivityRegistration::STATUS_REGISTERED,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return RpActivityRegistration[]
     */
    public function findActiveByActivityAndUser(RpActivity $activity, User $user): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.character', 'c')
            ->andWhere('r.activity = :activity')
            ->andWhere('c.user = :user')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('activity', $activity)
            ->setParameter('user', $user)
            ->setParameter('statuses', [
                RpActivityRegistration::STATUS_PENDING,
                RpActivityRegistration::STATUS_REGISTERED,
            ])
            ->getQuery()
            ->getResult();
    }
}
