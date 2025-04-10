<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\CharacterRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CharacterRelation>
 *
 * @method CharacterRelation|null find($id, $lockMode = null, $lockVersion = null)
 * @method CharacterRelation|null findOneBy(array $criteria, array $orderBy = null)
 * @method CharacterRelation[]    findAll()
 * @method CharacterRelation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CharacterRelationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CharacterRelation::class);
    }

    /**
     * @return CharacterRelation[] Returns an array of CharacterRelation objects for a character
     */
    public function findCharacterRelations(Character $character): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.sourceCharacter = :character OR r.targetCharacter = :character')
            ->setParameter('character', $character)
            ->orderBy('r.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}