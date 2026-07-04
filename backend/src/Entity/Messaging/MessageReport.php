<?php

namespace App\Entity\Messaging;

use App\Entity\User;
use App\Repository\Messaging\MessageReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageReportRepository::class)]
#[ORM\Table(name: 'messaging_message_report')]
class MessageReport
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    
    const REASON_INAPPROPRIATE = 'inappropriate';
    const REASON_HARASSMENT = 'harassment';
    const REASON_SPAM = 'spam';
    const REASON_OFFENSIVE = 'offensive';
    const REASON_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Message $message = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reporter = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $moderator = null;

    #[ORM\Column(length: 50)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moderationNotes = null;

    /**
     * Copie HTML du message au moment du signalement (inchangée si le message est édité ou supprimé ensuite).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reportedContentSnapshot = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $reporter): static
    {
        $this->reporter = $reporter;

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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getModerationNotes(): ?string
    {
        return $this->moderationNotes;
    }

    public function setModerationNotes(?string $moderationNotes): static
    {
        $this->moderationNotes = $moderationNotes;

        return $this;
    }

    public function getReportedContentSnapshot(): ?string
    {
        return $this->reportedContentSnapshot;
    }

    public function setReportedContentSnapshot(?string $reportedContentSnapshot): static
    {
        $this->reportedContentSnapshot = $reportedContentSnapshot;

        return $this;
    }

    /**
     * Contenu à afficher pour la modération : copie figée si présente, sinon le message actuel (anciens signalements).
     */
    public function getBodyForModeration(): string
    {
        $snap = $this->reportedContentSnapshot;
        if (null !== $snap && '' !== $snap) {
            return $snap;
        }

        return (string) ($this->message?->getContent() ?? '');
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

    public function getResolvedAt(): ?\DateTimeInterface
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeInterface $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getReasonLabel(): string
    {
        return match($this->reason) {
            self::REASON_INAPPROPRIATE => 'Contenu inapproprié',
            self::REASON_HARASSMENT => 'Harcèlement',
            self::REASON_SPAM => 'Spam',
            self::REASON_OFFENSIVE => 'Contenu offensant',
            self::REASON_OTHER => 'Autre raison',
            default => 'Raison inconnue'
        };
    }

    public static function getReasonChoices(): array
    {
        return [
            'Contenu inapproprié' => self::REASON_INAPPROPRIATE,
            'Harcèlement' => self::REASON_HARASSMENT,
            'Spam' => self::REASON_SPAM,
            'Contenu offensant' => self::REASON_OFFENSIVE,
            'Autre raison' => self::REASON_OTHER,
        ];
    }
} 