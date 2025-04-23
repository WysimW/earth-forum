<?php

namespace App\Entity\Messaging;

use App\Entity\User;
use App\Repository\Messaging\ConversationParticipantRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity(repositoryClass: ConversationParticipantRepository::class)]
#[ApiResource]
class ConversationParticipant
{
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_MEMBER = 'member';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastReadAt = null;

    #[ORM\Column(options: ["default" => true])]
    private bool $isActive = true;

    #[ORM\Column(options: ["default" => false])]
    private bool $isMuted = false;

    #[ORM\Column(options: ["default" => true])]
    private bool $canRead = true;

    #[ORM\Column(options: ["default" => true])]
    private bool $canWrite = true;

    #[ORM\Column(options: ["default" => false])]
    private bool $canManageMembers = false;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_MODERATOR, self::ROLE_MEMBER])) {
            throw new \InvalidArgumentException("Rôle de participant invalide");
        }
        
        $this->role = $role;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getLastReadAt(): ?\DateTimeInterface
    {
        return $this->lastReadAt;
    }

    public function setLastReadAt(?\DateTimeInterface $lastReadAt): static
    {
        $this->lastReadAt = $lastReadAt;

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

    public function isMuted(): bool
    {
        return $this->isMuted;
    }

    public function setIsMuted(bool $isMuted): static
    {
        $this->isMuted = $isMuted;

        return $this;
    }

    public function canRead(): bool
    {
        return $this->canRead;
    }

    public function setCanRead(bool $canRead): static
    {
        $this->canRead = $canRead;

        return $this;
    }

    public function canWrite(): bool
    {
        return $this->canWrite;
    }

    public function setCanWrite(bool $canWrite): static
    {
        $this->canWrite = $canWrite;

        return $this;
    }

    public function canManageMembers(): bool
    {
        return $this->canManageMembers;
    }

    public function setCanManageMembers(bool $canManageMembers): static
    {
        $this->canManageMembers = $canManageMembers;

        return $this;
    }

    /**
     * Vérifie si le participant a le rôle de modérateur ou d'administrateur
     */
    public function canModerate(): bool
    {
        return $this->isActive && ($this->role === self::ROLE_ADMIN || $this->role === self::ROLE_MODERATOR);
    }
    
    /**
     * Retourne le label du rôle pour l'affichage
     */
    public function getRoleLabel(): string
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_MODERATOR => 'Modérateur',
            self::ROLE_MEMBER => 'Membre',
            default => 'Inconnu'
        };
    }
    
    /**
     * Liste des rôles disponibles pour les formulaires
     */
    public static function getRoleChoices(): array
    {
        return [
            'Administrateur' => self::ROLE_ADMIN,
            'Modérateur' => self::ROLE_MODERATOR,
            'Membre' => self::ROLE_MEMBER,
        ];
    }
} 