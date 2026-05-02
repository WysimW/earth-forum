<?php

namespace App\Entity;

use App\Repository\UserDialogueThemeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDialogueThemeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserDialogueTheme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'dialogueThemes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 120)]
    private ?string $name = null;

    #[ORM\Column(length: 7)]
    private ?string $color = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $fontFamily = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isBold = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isItalic = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isDefault = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getFontFamily(): ?string
    {
        return $this->fontFamily;
    }

    public function setFontFamily(?string $fontFamily): static
    {
        $this->fontFamily = $fontFamily;

        return $this;
    }

    public function isBold(): bool
    {
        return $this->isBold;
    }

    public function setIsBold(bool $isBold): static
    {
        $this->isBold = $isBold;

        return $this;
    }

    public function isItalic(): bool
    {
        return $this->isItalic;
    }

    public function setIsItalic(bool $isItalic): static
    {
        $this->isItalic = $isItalic;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
