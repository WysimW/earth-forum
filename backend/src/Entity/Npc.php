<?php

namespace App\Entity;

use App\Repository\NpcRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Interface\TimestampableInterface;
use App\Entity\Trait\TimestampableTrait;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: NpcRepository::class)]
#[ORM\Table(name: '`npc`')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Npc implements TimestampableInterface
{
    use TimestampableTrait;
    
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_EDITING = 'editing';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;
    
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;
    
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pseudonyms = null;
    
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $gender = null;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $moralAffiliation = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $factions = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $occupation = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $equipment = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $weaknesses = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $age = null;

    #[ORM\ManyToOne(inversedBy: 'npcs')]
    private ?Univers $universe = null;

    #[ORM\ManyToOne(inversedBy: 'npcs')]
    private ?Elseworld $elseworld = null;

    #[ORM\ManyToOne(inversedBy: 'npcs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biography = null;
    
    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $personality = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $appearance = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $abilities = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $statusMessage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moderationNote = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = "default";

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $roleInStory = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $relationships = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $quests = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dialogueStyle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $secrets = null;
    
    public function __construct()
    {
        $this->status = self::STATUS_DRAFT;
    }

    // Getters et Setters pour les propriétés héritées de Character
    public function getId(): ?int
    {
        return $this->id;
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPseudonyms(): ?string
    {
        return $this->pseudonyms;
    }

    public function setPseudonyms(?string $pseudonyms): static
    {
        $this->pseudonyms = $pseudonyms;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getMoralAffiliation(): ?string
    {
        return $this->moralAffiliation;
    }

    public function setMoralAffiliation(?string $moralAffiliation): static
    {
        $this->moralAffiliation = $moralAffiliation;
        return $this;
    }

    public function getFactions(): ?string
    {
        return $this->factions;
    }

    public function setFactions(?string $factions): static
    {
        $this->factions = $factions;
        return $this;
    }

    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    public function setOccupation(?string $occupation): static
    {
        $this->occupation = $occupation;
        return $this;
    }

    public function getEquipment(): ?string
    {
        return $this->equipment;
    }

    public function setEquipment(?string $equipment): static
    {
        $this->equipment = $equipment;
        return $this;
    }

    public function getWeaknesses(): ?string
    {
        return $this->weaknesses;
    }

    public function setWeaknesses(?string $weaknesses): static
    {
        $this->weaknesses = $weaknesses;
        return $this;
    }

    public function getAge(): ?string
    {
        return $this->age;
    }

    public function setAge(?string $age): static
    {
        $this->age = $age;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): static
    {
        $this->biography = $biography;
        return $this;
    }

    public function getPersonality(): ?string
    {
        return $this->personality;
    }

    public function setPersonality(?string $personality): static
    {
        $this->personality = $personality;
        return $this;
    }

    public function getAppearance(): ?string
    {
        return $this->appearance;
    }

    public function setAppearance(?string $appearance): static
    {
        $this->appearance = $appearance;
        return $this;
    }

    public function getAbilities(): ?string
    {
        return $this->abilities;
    }

    public function setAbilities(?string $abilities): static
    {
        $this->abilities = $abilities;
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

    public function getUniverse(): ?Univers
    {
        return $this->universe;
    }

    public function setUniverse(?Univers $universe): static
    {
        $this->universe = $universe;
        return $this;
    }

    public function getElseworld(): ?Elseworld
    {
        return $this->elseworld;
    }

    public function setElseworld(?Elseworld $elseworld): static
    {
        $this->elseworld = $elseworld;
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

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    public function setStatusMessage(?string $statusMessage): static
    {
        $this->statusMessage = $statusMessage;
        return $this;
    }

    public function getModerationNote(): ?string
    {
        return $this->moderationNote;
    }

    public function setModerationNote(?string $moderationNote): static
    {
        $this->moderationNote = $moderationNote;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getRoleInStory(): ?string
    {
        return $this->roleInStory;
    }

    public function setRoleInStory(?string $roleInStory): static
    {
        $this->roleInStory = $roleInStory;
        return $this;
    }

    public function getRelationships(): ?string
    {
        return $this->relationships;
    }

    public function setRelationships(?string $relationships): static
    {
        $this->relationships = $relationships;
        return $this;
    }

    public function getQuests(): ?string
    {
        return $this->quests;
    }

    public function setQuests(?string $quests): static
    {
        $this->quests = $quests;
        return $this;
    }

    public function getDialogueStyle(): ?string
    {
        return $this->dialogueStyle;
    }

    public function setDialogueStyle(?string $dialogueStyle): self
    {
        $this->dialogueStyle = $dialogueStyle;
        return $this;
    }

    public function getSecrets(): ?string
    {
        return $this->secrets;
    }

    public function setSecrets(?string $secrets): static
    {
        $this->secrets = $secrets;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSlug(): void
    {
        $slugger = new AsciiSlugger();
        $this->slug = $slugger->slug($this->name)->lower();
    }

    public function getOwner(): ?User
    {
        return $this->user;
    }
} 