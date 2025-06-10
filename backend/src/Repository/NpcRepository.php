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

    /**
     * Trouve les PNJ disponibles pour un utilisateur dans un univers donné
     * Inclut les PNJ créés par l'utilisateur ET les PNJ des factions dont ses personnages sont membres
     */
    public function findAvailableForUser($user, $universe): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.factionsRelation', 'f')
            ->leftJoin('f.characters', 'c')
            ->where('n.status = :status')
            ->andWhere('(n.universe = :universe OR n.elseworld IN (
                SELECT e FROM App\Entity\Elseworld e WHERE e.parentUniverse = :universe
            ))')
            ->andWhere('(n.user = :user OR (c.user = :user AND c.status = :characterStatus))')
            ->setParameter('status', 'validated')
            ->setParameter('characterStatus', 'validated')
            ->setParameter('universe', $universe)
            ->setParameter('user', $user)
            ->orderBy('n.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 