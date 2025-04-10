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
            ->setParameter('threadId', $thread->getId())
            ->orderBy('c.name', 'ASC');
            
        // Si le thread est lié à un univers, on filtre les personnages par univers
        if ($thread->getUnivers()) {
            $queryBuilder->andWhere('c.univers = :univers')
                ->setParameter('univers', $thread->getUnivers());
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
            ->setParameter('user', $user)
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
            ->andWhere('c.univers = :univers')
            ->setParameter('univers', $univers)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findValidatedCharactersForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'validated')
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
            ->setParameter('user', $user)
            ->setParameter('status', 'validated')
            ->setParameter('thread', $thread)
            ->getQuery()
            ->getResult();
    }
}
