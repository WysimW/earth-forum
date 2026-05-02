<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserDialogueTheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDialogueTheme>
 */
class UserDialogueThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDialogueTheme::class);
    }

    /**
     * @return UserDialogueTheme[]
     */
    public function findByUserOrdered(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.isDefault', 'DESC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function clearDefaultForUser(User $user, ?int $excludeThemeId = null): void
    {
        $qb = $this->createQueryBuilder('t')
            ->update()
            ->set('t.isDefault', ':default')
            ->where('t.user = :user')
            ->setParameter('default', false)
            ->setParameter('user', $user);

        if ($excludeThemeId !== null) {
            $qb->andWhere('t.id != :excludeThemeId')
                ->setParameter('excludeThemeId', $excludeThemeId);
        }

        $qb->getQuery()->execute();
    }
}
