<?php

namespace App\Repository;

use App\Entity\MemberOfMonthMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MemberOfMonthMessage>
 */
class MemberOfMonthMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MemberOfMonthMessage::class);
    }
}

