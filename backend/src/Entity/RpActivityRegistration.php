<?php

namespace App\Entity;

use App\Repository\RpActivityRegistrationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RpActivityRegistrationRepository::class)]
#[ORM\Table(name: 'rp_activity_registration')]
#[ORM\UniqueConstraint(name: 'uniq_activity_character', columns: ['activity_id', 'character_id'])]
class RpActivityRegistration
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_WITHDRAWN = 'withdrawn';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?RpActivity $activity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Character $character = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $registeredAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $withdrawnAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivity(): ?RpActivity
    {
        return $this->activity;
    }

    public function setActivity(?RpActivity $activity): static
    {
        $this->activity = $activity;
        return $this;
    }

    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    public function setCharacter(?Character $character): static
    {
        $this->character = $character;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeImmutable $registeredAt): static
    {
        $this->registeredAt = $registeredAt;
        return $this;
    }

    public function getWithdrawnAt(): ?\DateTimeImmutable
    {
        return $this->withdrawnAt;
    }

    public function setWithdrawnAt(?\DateTimeImmutable $withdrawnAt): static
    {
        $this->withdrawnAt = $withdrawnAt;
        return $this;
    }

    public function isRegistered(): bool
    {
        return $this->status === self::STATUS_REGISTERED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
