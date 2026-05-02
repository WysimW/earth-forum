<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Faction;
use App\Entity\FactionCharacterMembership;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FactionCharacterMembership>
 */
class FactionCharacterMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactionCharacterMembership::class);
    }

    public function findOneByFactionAndCharacter(Faction $faction, Character $character): ?FactionCharacterMembership
    {
        return $this->findOneBy([
            'faction' => $faction,
            'character' => $character,
        ]);
    }
}

