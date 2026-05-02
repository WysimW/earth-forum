<?php

namespace App\Entity;

use App\Repository\MemberOfMonthMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MemberOfMonthMessageRepository::class)]
#[ORM\Table(name: 'member_of_month_message')]
class MemberOfMonthMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UniverseMemberOfMonth::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'member_of_month_id', nullable: false, onDelete: 'CASCADE')]
    private ?UniverseMemberOfMonth $memberOfMonth = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMemberOfMonth(): ?UniverseMemberOfMonth
    {
        return $this->memberOfMonth;
    }

    public function setMemberOfMonth(?UniverseMemberOfMonth $memberOfMonth): static
    {
        $this->memberOfMonth = $memberOfMonth;

        return $this;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}

