<?php

namespace App\Entity;

use App\Repository\CharacterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Interface\TimestampableInterface;
use App\Entity\Trait\TimestampableTrait;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '`character`')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [],
    routePrefix: '/admin/characters'
)]
class Character implements TimestampableInterface
{
    use TimestampableTrait;
    
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_EDITING = 'editing';
    public const KIND_STANDARD = 'standard';
    public const KIND_EVENT = 'event';

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
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $actualPseudo = null;
    
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $gender = null;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sexualOrientation = null;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $moralAffiliation = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $factions = null;
    
    /**
     * @var Collection<int, Faction>
     */
    #[ORM\ManyToMany(targetEntity: Faction::class, mappedBy: 'characters')]
    private Collection $factionsRelation;

    /**
     * @var Collection<int, FactionCharacterMembership>
     */
    #[ORM\OneToMany(targetEntity: FactionCharacterMembership::class, mappedBy: 'character', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $factionMemberships;

    /**
     * @var Collection<int, FactionCharacterApplication>
     */
    #[ORM\OneToMany(targetEntity: FactionCharacterApplication::class, mappedBy: 'character', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $factionApplications;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $civilStatus = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $occupation = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $equipment = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $weaknesses = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $age = null;

    #[ORM\ManyToOne(inversedBy: 'characters')]
    private ?Univers $universe = null;

    #[ORM\ManyToOne(inversedBy: 'characters')]
    private ?Elseworld $elseworld = null;

    #[ORM\ManyToOne(inversedBy: 'characters')]
    private ?User $user = null;

    #[ORM\Column(length: 20, options: ['default' => self::KIND_STANDARD])]
    private string $kind = self::KIND_STANDARD;

    #[ORM\ManyToOne(inversedBy: 'eventCharacters')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?RpActivity $eventActivity = null;
    
    #[ORM\ManyToOne(inversedBy: 'characters')]
    private ?Location $location = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $avatar = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biography = null;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $alias = null;
    
    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $personality = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $appearance = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $abilities = null;
    
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'character')]
    #[Ignore]
    private Collection $posts;
    
    #[ORM\OneToMany(targetEntity: Thread::class, mappedBy: 'characterCreator')]
    #[Ignore]
    private Collection $threads;

    #[ORM\OneToMany(targetEntity: Thread::class, mappedBy: 'characterSheet')]
    #[Ignore]
    private Collection $characterSheetThread;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $statusMessage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moderationNote = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = "default";

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $rejectedAt = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dialogueStyle = null;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sheetTheme = 'default';

    /**
     * @var Collection<int, RpActivityRegistration>
     */
    #[ORM\OneToMany(targetEntity: RpActivityRegistration::class, mappedBy: 'character', orphanRemoval: true)]
    private Collection $rpActivityRegistrations;
    
    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->threads = new ArrayCollection();
        $this->characterSheetThread = new ArrayCollection();
        $this->factionsRelation = new ArrayCollection();
        $this->factionMemberships = new ArrayCollection();
        $this->factionApplications = new ArrayCollection();
        $this->rpActivityRegistrations = new ArrayCollection();
        $this->status = self::STATUS_DRAFT;
    }

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
    
    public function getActualPseudo(): ?string
    {
        return $this->actualPseudo;
    }
    
    public function setActualPseudo(?string $actualPseudo): static
    {
        $this->actualPseudo = $actualPseudo;
        
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
    
    public function getSexualOrientation(): ?string
    {
        return $this->sexualOrientation;
    }
    
    public function setSexualOrientation(?string $sexualOrientation): static
    {
        $this->sexualOrientation = $sexualOrientation;
        
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
    
    /**
     * @return Collection<int, Faction>
     */
    public function getFactionsRelation(): Collection
    {
        return $this->factionsRelation;
    }
    
    public function addFactionRelation(Faction $faction): static
    {
        if (!$this->factionsRelation->contains($faction)) {
            $this->factionsRelation->add($faction);
            $faction->addCharacter($this);
        }
        
        return $this;
    }
    
    public function removeFactionRelation(Faction $faction): static
    {
        if ($this->factionsRelation->removeElement($faction)) {
            $faction->removeCharacter($this);
        }
        
        return $this;
    }

    /**
     * @return Collection<int, FactionCharacterMembership>
     */
    public function getFactionMemberships(): Collection
    {
        return $this->factionMemberships;
    }

    public function addFactionMembership(FactionCharacterMembership $membership): static
    {
        if (!$this->factionMemberships->contains($membership)) {
            $this->factionMemberships->add($membership);
            $membership->setCharacter($this);
        }

        return $this;
    }

    public function removeFactionMembership(FactionCharacterMembership $membership): static
    {
        if ($this->factionMemberships->removeElement($membership)) {
            if ($membership->getCharacter() === $this) {
                $membership->setCharacter(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FactionCharacterApplication>
     */
    public function getFactionApplications(): Collection
    {
        return $this->factionApplications;
    }

    public function addFactionApplication(FactionCharacterApplication $application): static
    {
        if (!$this->factionApplications->contains($application)) {
            $this->factionApplications->add($application);
            $application->setCharacter($this);
        }

        return $this;
    }

    public function removeFactionApplication(FactionCharacterApplication $application): static
    {
        if ($this->factionApplications->removeElement($application)) {
            if ($application->getCharacter() === $this) {
                $application->setCharacter(null);
            }
        }

        return $this;
    }
    
    public function getCivilStatus(): ?string
    {
        return $this->civilStatus;
    }
    
    public function setCivilStatus(?string $civilStatus): static
    {
        $this->civilStatus = $civilStatus;
        
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

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

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

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind(string $kind): static
    {
        $this->kind = $kind;
        return $this;
    }

    public function getEventActivity(): ?RpActivity
    {
        return $this->eventActivity;
    }

    public function setEventActivity(?RpActivity $eventActivity): static
    {
        $this->eventActivity = $eventActivity;
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
    
    public function getAlias(): ?string
    {
        return $this->alias;
    }
    
    public function setAlias(?string $alias): static
    {
        $this->alias = $alias;
        
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
    
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }


    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
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

    public function getRejectedAt(): ?\DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function setRejectedAt(?\DateTimeImmutable $rejectedAt): static
    {
        $this->rejectedAt = $rejectedAt;

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

    public function getSheetTheme(): ?string
    {
        return $this->sheetTheme ?? 'default';
    }

    public function setSheetTheme(?string $sheetTheme): static
    {
        $this->sheetTheme = $sheetTheme ?? 'default';
        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->initializeTimestamps();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updateTimestamps();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSlug(): void
    {
        if (empty($this->slug) && !empty($this->name)) {
            $slugger = new AsciiSlugger();
            $this->slug = strtolower($slugger->slug($this->name));
        }
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isAbandoned(): bool
    {
        return $this->status === self::STATUS_ABANDONED;
    }

    public function isEditing(): bool
    {
        return $this->status === self::STATUS_EDITING;
    }

    public function isEventCharacter(): bool
    {
        return $this->kind === self::KIND_EVENT;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }
    
    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setCharacter($this);
        }
        
        return $this;
    }
    
    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getCharacter() === $this) {
                $post->setCharacter(null);
            }
        }
        
        return $this;
    }
    
    /**
     * @return Collection<int, Thread>
     */
    public function getThreads(): Collection
    {
        return $this->threads;
    }
    
    public function addThread(Thread $thread): static
    {
        if (!$this->threads->contains($thread)) {
            $this->threads->add($thread);
            $thread->setCharacterCreator($this);
        }
        
        return $this;
    }
    
    public function removeThread(Thread $thread): static
    {
        if ($this->threads->removeElement($thread)) {
            // set the owning side to null (unless already changed)
            if ($thread->getCharacterCreator() === $this) {
                $thread->setCharacterCreator(null);
            }
        }
        
        return $this;
    }

    /**
     * @return Collection<int, Thread>
     */
    public function getCharacterSheetThread(): Collection
    {
        return $this->characterSheetThread;
    }
    
    public function addCharacterSheetThread(Thread $thread): static
    {
        if (!$this->characterSheetThread->contains($thread)) {
            $this->characterSheetThread->add($thread);
            $thread->setCharacterSheet($this);
        }
        
        return $this;
    }
    
    public function removeCharacterSheetThread(Thread $thread): static
    {
        if ($this->characterSheetThread->removeElement($thread)) {
            // set the owning side to null (unless already changed)
            if ($thread->getCharacterSheet() === $this) {
                $thread->setCharacterSheet(null);
            }
        }
        
        return $this;
    }
    
    public function getMainCharacterSheetThread(): ?Thread
    {
        return $this->characterSheetThread->isEmpty() ? null : $this->characterSheetThread->first();
    }

    public function getOwner(): ?User
    {
        return $this->user;
    }

    /**
     * @return Collection<int, RpActivityRegistration>
     */
    public function getRpActivityRegistrations(): Collection
    {
        return $this->rpActivityRegistrations;
    }

    public function addRpActivityRegistration(RpActivityRegistration $rpActivityRegistration): static
    {
        if (!$this->rpActivityRegistrations->contains($rpActivityRegistration)) {
            $this->rpActivityRegistrations->add($rpActivityRegistration);
            $rpActivityRegistration->setCharacter($this);
        }

        return $this;
    }

    public function removeRpActivityRegistration(RpActivityRegistration $rpActivityRegistration): static
    {
        if ($this->rpActivityRegistrations->removeElement($rpActivityRegistration)) {
            if ($rpActivityRegistration->getCharacter() === $this) {
                $rpActivityRegistration->setCharacter(null);
            }
        }

        return $this;
    }
}
