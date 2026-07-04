<?php

namespace App\Repository;

use App\Entity\Univers;
use App\Entity\User;
use App\Entity\UserImportantSeen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserImportantSeen>
 */
class UserImportantSeenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserImportantSeen::class);
    }

    public function findOneFor(User $user, Univers $universe, string $contentType): ?UserImportantSeen
    {
        return $this->findOneBy([
            'user' => $user,
            'universe' => $universe,
            'contentType' => $contentType,
        ]);
    }

    public function markSeen(User $user, Univers $universe, string $contentType, ?int $entryId): void
    {
        $record = $this->findOneFor($user, $universe, $contentType) ?? (new UserImportantSeen())
            ->setUser($user)
            ->setUniverse($universe)
            ->setContentType($contentType);

        $record
            ->setLastSeenEntryId($entryId)
            ->setSeenAt(new \DateTimeImmutable());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($record);
        $entityManager->flush();
    }
}
