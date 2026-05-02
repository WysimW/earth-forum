<?php

namespace App\Repository;

use App\Entity\UniverseCharacterOfMonth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UniverseCharacterOfMonth>
 */
class UniverseCharacterOfMonthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UniverseCharacterOfMonth::class);
    }
}

