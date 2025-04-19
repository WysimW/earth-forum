<?php

namespace App\Repository;

use App\Entity\Faction;
use App\Entity\User;
use App\Entity\Univers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Faction>
 *
 * @method Faction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Faction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Faction[]    findAll()
 * @method Faction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faction::class);
    }

    public function save(Faction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Faction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les factions par univers
     */
    public function findByUniverse($universeId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.universe = :universeId')
            ->setParameter('universeId', $universeId)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les factions ouvertes aux inscriptions
     */
    public function findOpenFactions(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.status = :status')
            ->setParameter('status', Faction::STATUS_OPEN)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les factions créées par un utilisateur
     */
    public function findByFounder($userId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.founder = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les factions auxquelles appartient un personnage
     */
    public function findByCharacter($characterId): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.characters', 'c')
            ->andWhere('c.id = :characterId')
            ->setParameter('characterId', $characterId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les factions auxquelles appartient un PNJ
     */
    public function findByNpc($npcId): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.npcs', 'n')
            ->andWhere('n.id = :npcId')
            ->setParameter('npcId', $npcId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les factions associées à un utilisateur dans un univers donné
     * Soit parce qu'il en est le fondateur, soit parce qu'il a un personnage qui en est membre
     */
    public function findFactionsForUser(User $user, Univers $univers): array
    {
        // Requête pour trouver les factions où l'utilisateur est fondateur OU
        // a un personnage qui est membre de la faction
        return $this->createQueryBuilder('f')
            ->leftJoin('f.characters', 'c')
            ->leftJoin('c.user', 'u')
            ->where('f.universe = :universe')
            ->andWhere('(f.founder = :user OR u.id = :userId)')
            ->setParameter('universe', $univers)
            ->setParameter('user', $user)
            ->setParameter('userId', $user->getId())
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 