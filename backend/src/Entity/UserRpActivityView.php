<?php

namespace App\Entity;

use App\Repository\UserRpActivityViewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRpActivityViewRepository::class)]
#[ORM\Table(name: 'user_rp_activity_view')]
#[ORM\UniqueConstraint(name: 'uniq_user_activity_view', columns: ['user_id', 'activity_id'])]
class UserRpActivityView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?RpActivity $activity = null;

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

    public function getActivity(): ?RpActivity
    {
        return $this->activity;
    }

    public function setActivity(?RpActivity $activity): static
    {
        $this->activity = $activity;
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
