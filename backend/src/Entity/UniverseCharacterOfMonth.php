<?php

namespace App\Entity;

use App\Repository\UniverseCharacterOfMonthRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UniverseCharacterOfMonthRepository::class)]
#[ORM\Table(name: 'universe_character_of_month')]
#[ORM\UniqueConstraint(name: 'uniq_universe_character_month', columns: ['universe_id', 'year', 'month'])]
class UniverseCharacterOfMonth
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Univers::class)]
    #[ORM\JoinColumn(name: 'universe_id', nullable: false, onDelete: 'CASCADE')]
    private ?Univers $universe = null;

    #[ORM\Column]
    private int $year = 0;

    #[ORM\Column]
    private int $month = 0;

    #[ORM\Column(length: 120)]
    private ?string $firstName = null;

    #[ORM\Column(length: 120)]
    private ?string $lastName = null;

    #[ORM\Column(length: 120)]
    private ?string $nickname = null;

    #[ORM\Column(length: 120)]
    private ?string $alignment = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $powers = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $weaknesses = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $whoIsText = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $whyPlayText = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $quickCreatePayload = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'selected_by_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $selectedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $selectedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->selectedAt = $now;
        $this->createdAt = $now;
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getAlignment(): ?string
    {
        return $this->alignment;
    }

    public function setAlignment(string $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function getPowers(): ?string
    {
        return $this->powers;
    }

    public function setPowers(string $powers): static
    {
        $this->powers = $powers;

        return $this;
    }

    public function getWeaknesses(): ?string
    {
        return $this->weaknesses;
    }

    public function setWeaknesses(string $weaknesses): static
    {
        $this->weaknesses = $weaknesses;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getWhoIsText(): ?string
    {
        return $this->whoIsText;
    }

    public function setWhoIsText(string $whoIsText): static
    {
        $this->whoIsText = $whoIsText;

        return $this;
    }

    public function getWhyPlayText(): ?string
    {
        return $this->whyPlayText;
    }

    public function setWhyPlayText(string $whyPlayText): static
    {
        $this->whyPlayText = $whyPlayText;

        return $this;
    }

    public function getQuickCreatePayload(): ?array
    {
        return $this->quickCreatePayload;
    }

    public function setQuickCreatePayload(?array $quickCreatePayload): static
    {
        $this->quickCreatePayload = $quickCreatePayload;

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
}

