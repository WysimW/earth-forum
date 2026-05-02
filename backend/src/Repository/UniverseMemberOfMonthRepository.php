<?php

namespace App\Repository;

use App\Entity\UniverseMemberOfMonth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UniverseMemberOfMonth>
 */
class UniverseMemberOfMonthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UniverseMemberOfMonth::class);
    }
}

