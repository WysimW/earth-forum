<?php

namespace App\Repository;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function countActiveSince(DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.isActive = :active')
            ->andWhere('u.lastLogin IS NOT NULL')
            ->andWhere('u.lastLogin >= :since')
            ->setParameter('active', true)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return User[]
     */
    public function findRecentlyOnlineUsers(DateTimeImmutable $since, int $limit = 8): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->andWhere('u.lastLogin IS NOT NULL')
            ->andWhere('u.lastLogin >= :since')
            ->setParameter('active', true)
            ->setParameter('since', $since)
            ->orderBy('u.lastLogin', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findRecentlyActiveUsers(DateTimeImmutable $since, int $limit = 20): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->andWhere('u.lastLogin IS NOT NULL')
            ->andWhere('u.lastLogin >= :since')
            ->setParameter('active', true)
            ->setParameter('since', $since)
            ->orderBy('u.lastLogin', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Membres actifs dont le pseudo contient le fragment (recherche messagerie, sans caractères jokers SQL).
     *
     * @return User[]
     */
    public function searchActiveMembersByPseudoFragment(string $fragment, int $excludeUserId, int $limit = 20): array
    {
        $fragment = trim($fragment);
        if ($fragment === '') {
            return [];
        }

        $safe = str_replace(['%', '_', '\\'], '', $fragment);
        if (mb_strlen($safe) < 2) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->andWhere('u.id != :excludeId')
            ->andWhere('LOWER(u.pseudo) LIKE :pattern')
            ->setParameter('active', true)
            ->setParameter('excludeId', $excludeUserId)
            ->setParameter('pattern', '%'.mb_strtolower($safe).'%')
            ->orderBy('u.pseudo', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
