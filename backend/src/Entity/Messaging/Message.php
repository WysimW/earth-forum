<?php

namespace App\Entity\Messaging;

use App\Entity\Character;
use App\Entity\User;
use App\Repository\Messaging\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ApiResource]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne]
    private ?Character $character = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    private bool $isEdited = false;

    #[ORM\Column(nullable: true)]
    private ?array $attachments = [];

    #[ORM\Column]
    private bool $isRoleplay = false;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $replyTo = null;

    #[ORM\Column]
    private bool $isDeleted = false;

    /**
     * @var Collection<int, MessageReport>
     */
    #[ORM\OneToMany(targetEntity: MessageReport::class, mappedBy: 'message', orphanRemoval: true)]
    private Collection $reports;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isFlagged = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isDeleted = false;
        $this->isRoleplay = false;
        $this->reports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isEdited(): bool
    {
        return $this->isEdited;
    }

    public function setIsEdited(bool $isEdited): static
    {
        $this->isEdited = $isEdited;

        return $this;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): static
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function isRoleplay(): bool
    {
        return $this->isRoleplay;
    }

    public function setIsRoleplay(bool $isRoleplay): static
    {
        $this->isRoleplay = $isRoleplay;

        return $this;
    }

    public function getReplyTo(): ?self
    {
        return $this->replyTo;
    }

    public function setReplyTo(?self $replyTo): static
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * @return Collection<int, MessageReport>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(MessageReport $report): static
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setMessage($this);
        }

        return $this;
    }

    public function removeReport(MessageReport $report): static
    {
        if ($this->reports->removeElement($report)) {
            // set the owning side to null (unless already changed)
            if ($report->getMessage() === $this) {
                $report->setMessage(null);
            }
        }

        return $this;
    }

    public function isFlagged(): bool
    {
        return $this->isFlagged;
    }

    public function setIsFlagged(bool $isFlagged): static
    {
        $this->isFlagged = $isFlagged;

        return $this;
    }

    // Méthode pour compter les signalements actifs
    public function countActiveReports(): int
    {
        $count = 0;
        foreach ($this->reports as $report) {
            if ($report->isPending()) {
                $count++;
            }
        }
        return $count;
    }
} 