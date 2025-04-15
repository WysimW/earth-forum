<?php

namespace App\Repository;

use App\Entity\Elseworld;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Elseworld>
 *
 * @method Elseworld|null find($id, $lockMode = null, $lockVersion = null)
 * @method Elseworld|null findOneBy(array $criteria, array $orderBy = null)
 * @method Elseworld[]    findAll()
 * @method Elseworld[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ElseworldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Elseworld::class);
    }

    public function save(Elseworld $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Elseworld $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve tous les Elseworlds liés à un univers parent spécifique
     *
     * @param int $universeId L'ID de l'univers parent
     * @return Elseworld[] Les Elseworlds liés à cet univers
     */
    public function findByParentUniverse(int $universeId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.parentUniverse = :universeId')
            ->setParameter('universeId', $universeId)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 