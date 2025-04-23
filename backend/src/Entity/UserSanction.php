<?php

namespace App\Entity;

use App\Repository\UserSanctionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSanctionRepository::class)]
class UserSanction
{
    const TYPE_WARNING = 'warning';            // Simple avertissement, pas de restriction
    const TYPE_MUTE = 'mute';                  // Interdiction d'envoyer des messages
    const TYPE_BAN_MESSAGING = 'ban_messaging'; // Interdiction d'utiliser la messagerie
    const TYPE_BAN_POSTING = 'ban_posting';     // Interdiction de créer des sujets/répondre
    const TYPE_FULL_BAN = 'full_ban';          // Interdiction totale (compte désactivé)

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $moderator = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moderatorNotes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $revokedAt = null;

    #[ORM\ManyToOne]
    private ?User $revokedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isActive = true;
    }

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

    public function getModerator(): ?User
    {
        return $this->moderator;
    }

    public function setModerator(?User $moderator): static
    {
        $this->moderator = $moderator;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

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

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getModeratorNotes(): ?string
    {
        return $this->moderatorNotes;
    }

    public function setModeratorNotes(?string $moderatorNotes): static
    {
        $this->moderatorNotes = $moderatorNotes;

        return $this;
    }

    public function getRevokedAt(): ?\DateTimeInterface
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?\DateTimeInterface $revokedAt): static
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }

    public function getRevokedBy(): ?User
    {
        return $this->revokedBy;
    }

    public function setRevokedBy(?User $revokedBy): static
    {
        $this->revokedBy = $revokedBy;

        return $this;
    }

    public function isExpired(): bool
    {
        if (!$this->expiresAt) {
            return false; // Sanction permanente
        }
        
        return $this->expiresAt < new \DateTime();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function getStatus(): string
    {
        if (!$this->isActive) {
            return 'inactive';
        }
        
        if ($this->isRevoked()) {
            return 'revoked';
        }
        
        if ($this->isExpired()) {
            return 'expired';
        }
        
        return 'active';
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_WARNING => 'Avertissement',
            self::TYPE_MUTE => 'Interdiction d\'envoyer des messages',
            self::TYPE_BAN_MESSAGING => 'Interdiction d\'utiliser la messagerie',
            self::TYPE_BAN_POSTING => 'Interdiction de poster',
            self::TYPE_FULL_BAN => 'Bannissement complet',
            default => 'Sanction inconnue'
        };
    }

    public static function getTypeChoices(): array
    {
        return [
            'Avertissement' => self::TYPE_WARNING,
            'Interdiction d\'envoyer des messages' => self::TYPE_MUTE,
            'Interdiction d\'utiliser la messagerie' => self::TYPE_BAN_MESSAGING,
            'Interdiction de poster' => self::TYPE_BAN_POSTING,
            'Bannissement complet' => self::TYPE_FULL_BAN,
        ];
    }

    public static function getDurationChoices(): array
    {
        return [
            '1 heure' => 'PT1H',
            '3 heures' => 'PT3H', 
            '12 heures' => 'PT12H',
            '1 jour' => 'P1D',
            '3 jours' => 'P3D',
            '1 semaine' => 'P1W',
            '2 semaines' => 'P2W',
            '1 mois' => 'P1M',
            '3 mois' => 'P3M',
            'Permanent' => 'permanent',
        ];
    }
} 