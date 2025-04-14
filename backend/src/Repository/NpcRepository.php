<?php

namespace App\Repository;

use App\Entity\Npc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Npc>
 *
 * @method Npc|null find($id, $lockMode = null, $lockVersion = null)
 * @method Npc|null findOneBy(array $criteria, array $orderBy = null)
 * @method Npc[]    findAll()
 * @method Npc[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NpcRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Npc::class);
    }

    public function findByUniverse($universeId)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.universe = :universeId')
            ->setParameter('universeId', $universeId)
            ->orderBy('n.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser($userId)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus($status)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.status = :status')
            ->setParameter('status', $status)
            ->orderBy('n.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 