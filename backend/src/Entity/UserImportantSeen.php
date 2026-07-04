<?php

namespace App\Entity;

use App\Repository\UserImportantSeenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserImportantSeenRepository::class)]
#[ORM\Table(name: 'user_important_seen')]
#[ORM\UniqueConstraint(name: 'uniq_user_universe_content', columns: ['user_id', 'universe_id', 'content_type'])]
class UserImportantSeen
{
    public const CONTENT_MEMBER_OF_MONTH = 'member_of_month';
    public const CONTENT_CHARACTER_OF_MONTH = 'character_of_month';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Univers $universe = null;

    #[ORM\Column(length: 40)]
    private ?string $contentType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $lastSeenEntryId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $seenAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getUniverse(): ?Univers
    {
        return $this->universe;
    }

    public function setUniverse(?Univers $universe): static
    {
        $this->universe = $universe;
        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): static
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function getLastSeenEntryId(): ?int
    {
        return $this->lastSeenEntryId;
    }

    public function setLastSeenEntryId(?int $lastSeenEntryId): static
    {
        $this->lastSeenEntryId = $lastSeenEntryId;
        return $this;
    }

    public function getSeenAt(): ?\DateTimeImmutable
    {
        return $this->seenAt;
    }

    public function setSeenAt(\DateTimeImmutable $seenAt): static
    {
        $this->seenAt = $seenAt;
        return $this;
    }
}
