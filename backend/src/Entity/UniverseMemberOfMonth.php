<?php

namespace App\Entity;

use App\Repository\UniverseMemberOfMonthRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UniverseMemberOfMonthRepository::class)]
#[ORM\Table(name: 'universe_member_of_month')]
#[ORM\UniqueConstraint(name: 'uniq_universe_member_month', columns: ['universe_id', 'year', 'month'])]
class UniverseMemberOfMonth
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Univers::class)]
    #[ORM\JoinColumn(name: 'universe_id', nullable: false, onDelete: 'CASCADE')]
    private ?Univers $universe = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    private int $year = 0;

    #[ORM\Column]
    private int $month = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $highlightText = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'selected_by_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $selectedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $selectedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, MemberOfMonthMessage>
     */
    #[ORM\OneToMany(targetEntity: MemberOfMonthMessage::class, mappedBy: 'memberOfMonth', orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $messages;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->selectedAt = $now;
        $this->createdAt = $now;
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getHighlightText(): ?string
    {
        return $this->highlightText;
    }

    public function setHighlightText(?string $highlightText): static
    {
        $this->highlightText = $highlightText;

        return $this;
    }

    public function getSelectedBy(): ?User
    {
        return $this->selectedBy;
    }

    public function setSelectedBy(?User $selectedBy): static
    {
        $this->selectedBy = $selectedBy;

        return $this;
    }

    public function getSelectedAt(): ?\DateTimeImmutable
    {
        return $this->selectedAt;
    }

    public function setSelectedAt(\DateTimeImmutable $selectedAt): static
    {
        $this->selectedAt = $selectedAt;

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

    /**
     * @return Collection<int, MemberOfMonthMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(MemberOfMonthMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setMemberOfMonth($this);
        }

        return $this;
    }

    public function removeMessage(MemberOfMonthMessage $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getMemberOfMonth() === $this) {
                $message->setMemberOfMonth(null);
            }
        }

        return $this;
    }
}

