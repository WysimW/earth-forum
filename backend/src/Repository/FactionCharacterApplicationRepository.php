<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Faction;
use App\Entity\FactionCharacterApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FactionCharacterApplication>
 */
class FactionCharacterApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactionCharacterApplication::class);
    }

    public function findOneByFactionAndCharacter(Faction $faction, Character $character): ?FactionCharacterApplication
    {
        return $this->findOneBy([
            'faction' => $faction,
            'character' => $character,
        ]);
    }

    /**
     * @return FactionCharacterApplication[]
     */
    public function findPendingByFaction(Faction $faction): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.character', 'c')
            ->addSelect('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->where('a.faction = :faction')
            ->andWhere('a.status = :status')
            ->setParameter('faction', $faction)
            ->setParameter('status', FactionCharacterApplication::STATUS_PENDING)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
