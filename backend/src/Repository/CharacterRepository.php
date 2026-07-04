<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Thread;
use App\Entity\Univers;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    //    /**
    //     * @return Character[] Returns an array of Character objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Character
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Trouve les personnages qui ne sont pas dans un thread spécifique
     * 
     * @param Thread $thread Le thread RP
     * @return Character[] Liste des personnages disponibles
     */
    public function findCharactersNotInThread(Thread $thread): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->where('c.id NOT IN (
                SELECT IDENTITY(p.id) FROM App\Entity\Thread t
                JOIN t.participants p
                WHERE t.id = :threadId
            )')
            ->andWhere('c.kind = :kind')
            ->setParameter('kind', Character::KIND_STANDARD)
            ->setParameter('threadId', $thread->getId())
            ->orderBy('c.name', 'ASC');
            
        // Si le thread est lié à un univers, on filtre les personnages par univers
        if ($thread->getUniverse()) {
            $queryBuilder->andWhere('c.universe = :universe')
                ->setParameter('universe', $thread->getUniverse());
        }
        
        return $queryBuilder->getQuery()->getResult();
    }
    
    /**
     * Trouve les personnages d'un utilisateur
     * 
     * @param User $user L'utilisateur
     * @return Character[] Liste des personnages de l'utilisateur
     */
    public function findCharactersByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.kind = :kind')
            ->setParameter('user', $user)
            ->setParameter('kind', Character::KIND_STANDARD)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les personnages appartenant à un univers donné
     * 
     * @param Univers $univers L'univers des personnages
     * @return Character[] Liste des personnages
     */
    public function findByUnivers(Univers $univers): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.universe = :univers')
            ->andWhere('c.kind = :kind')
            ->setParameter('univers', $univers)
            ->setParameter('kind', Character::KIND_STANDARD)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les personnages validés d'un univers (inclut les elseworlds de cet univers).
     *
     * @return Character[]
     */
    public function findValidatedByUniverseSlug(string $universeSlug): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.universe', 'u')
            ->leftJoin('c.elseworld', 'e')
            ->leftJoin('e.parentUniverse', 'pu')
            ->andWhere('(u.slug = :slug OR pu.slug = :slug)')
            ->andWhere('c.status = :status')
            ->andWhere('c.kind = :kind')
            ->setParameter('slug', $universeSlug)
            ->setParameter('status', 'validated')
            ->setParameter('kind', Character::KIND_STANDARD)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les personnages validés d'un utilisateur sur un univers donné
     * (inclut les elseworlds de cet univers).
     *
     * @return Character[]
     */
    public function findValidatedByUniverseSlugAndUser(string $universeSlug, User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.universe', 'u')
            ->leftJoin('c.elseworld', 'e')
            ->leftJoin('e.parentUniverse', 'pu')
            ->andWhere('(u.slug = :slug OR pu.slug = :slug)')
            ->andWhere('c.status = :status')
            ->andWhere('c.user = :user')
            ->andWhere('c.kind = :kind')
            ->setParameter('slug', $universeSlug)
            ->setParameter('status', 'validated')
            ->setParameter('user', $user)
            ->setParameter('kind', Character::KIND_STANDARD)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findValidatedCharactersForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.status = :status')
            ->andWhere('c.kind = :kind')
            ->setParameter('user', $user)
            ->setParameter('status', 'validated')
            ->setParameter('kind', Character::KIND_STANDARD)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findValidatedParticipantsForUser(User $user, Thread $thread): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.status = :status')
            ->andWhere(':thread MEMBER OF c.threads')
            ->andWhere('c.kind = :kind')
            ->setParameter('user', $user)
            ->setParameter('status', 'validated')
            ->setParameter('thread', $thread)
            ->setParameter('kind', Character::KIND_STANDARD)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les personnages validés d'un utilisateur pour un univers spécifique
     */
    public function findValidatedCharactersForUserAndUniverse(User $user, Univers $universe): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.status = :status')
            ->andWhere('c.kind = :kind')
            ->andWhere('c.universe = :universe OR c.elseworld IN (
                SELECT e FROM App\Entity\Elseworld e WHERE e.parentUniverse = :universe
            )')
            ->setParameter('user', $user)
            ->setParameter('status', 'validated')
            ->setParameter('kind', Character::KIND_STANDARD)
            ->setParameter('universe', $universe)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
