<?php

namespace App\Repository;

use App\Entity\AIPersona;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AIPersona>
 *
 * @method AIPersona|null find($id, $lockMode = null, $lockVersion = null)
 * @method AIPersona|null findOneBy(array $criteria, array $orderBy = null)
 * @method AIPersona[]    findAll()
 * @method AIPersona[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AIPersonaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AIPersona::class);
    }

    /**
     * Trouve tous les personas actifs
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les personas associés à un personnage
     */
    public function findByCharacter(int $characterId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.character = :characterId')
            ->setParameter('characterId', $characterId)
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les personas créés par un utilisateur
     */
    public function findByCreator(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.creator = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 