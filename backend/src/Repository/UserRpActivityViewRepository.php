<?php

namespace App\Repository;

use App\Entity\RpActivity;
use App\Entity\User;
use App\Entity\UserRpActivityView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRpActivityView>
 */
class UserRpActivityViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRpActivityView::class);
    }

    public function findOneFor(User $user, RpActivity $activity): ?UserRpActivityView
    {
        return $this->findOneBy([
            'user' => $user,
            'activity' => $activity,
        ]);
    }

    public function markSeen(User $user, RpActivity $activity): void
    {
        $view = $this->findOneFor($user, $activity) ?? (new UserRpActivityView())
            ->setUser($user)
            ->setActivity($activity);

        $view->setSeenAt(new \DateTimeImmutable());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($view);
        $entityManager->flush();
    }

    /**
     * @param int[] $activityIds
     * @return array<int,\DateTimeImmutable>
     */
    public function getSeenAtByActivityIds(User $user, array $activityIds): array
    {
        $activityIds = array_values(array_unique(array_filter(array_map('intval', $activityIds))));
        if ($activityIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.activity) AS activityId', 'v.seenAt AS seenAt')
            ->where('v.user = :user')
            ->andWhere('v.activity IN (:activityIds)')
            ->setParameter('user', $user)
            ->setParameter('activityIds', $activityIds)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $activityId = (int) $row['activityId'];
            $seenAt = $row['seenAt'];
            if ($seenAt instanceof \DateTimeImmutable) {
                $result[$activityId] = $seenAt;
            } elseif ($seenAt instanceof \DateTimeInterface) {
                $result[$activityId] = \DateTimeImmutable::createFromMutable(
                    \DateTime::createFromInterface($seenAt)
                );
            }
        }

        return $result;
    }

    /**
     * @param RpActivity[] $activities
     */
    public function markManySeen(User $user, array $activities): void
    {
        $entityManager = $this->getEntityManager();
        foreach ($activities as $activity) {
            if (!$activity instanceof RpActivity) {
                continue;
            }
            $view = $this->findOneFor($user, $activity) ?? (new UserRpActivityView())
                ->setUser($user)
                ->setActivity($activity);
            $view->setSeenAt(new \DateTimeImmutable());
            $entityManager->persist($view);
        }
        $entityManager->flush();
    }
}
