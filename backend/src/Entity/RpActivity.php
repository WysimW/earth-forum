<?php

namespace App\Entity;

use App\Entity\Interface\TimestampableInterface;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\RpActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RpActivityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RpActivity implements TimestampableInterface
{
    use TimestampableTrait;

    public const KIND_EVENT = 'event';
    public const KIND_MISSION = 'mission';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $kind = self::KIND_EVENT;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $openingSpeech = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $illustrationUrl = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $reminderAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $registrationEndAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $reminderSentAt = null;

    #[ORM\ManyToOne(inversedBy: 'rpActivities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Univers $universe = null;

    #[ORM\ManyToOne(inversedBy: 'rpActivities')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Faction $faction = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, Character>
     */
    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'eventActivity')]
    private Collection $eventCharacters;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'rp_activity_allowed_user')]
    private Collection $allowedUsers;

    /**
     * @var Collection<int, Thread>
     */
    #[ORM\ManyToMany(targetEntity: Thread::class, inversedBy: 'rpActivities')]
    #[ORM\JoinTable(name: 'rp_activity_thread')]
    private Collection $threads;

    /**
     * @var Collection<int, RpActivityRegistration>
     */
    #[ORM\OneToMany(targetEntity: RpActivityRegistration::class, mappedBy: 'activity', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $registrations;

    public function __construct()
    {
        $this->threads = new ArrayCollection();
        $this->registrations = new ArrayCollection();
        $this->eventCharacters = new ArrayCollection();
        $this->allowedUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getOpeningSpeech(): ?string
    {
        return $this->openingSpeech;
    }

    public function setOpeningSpeech(?string $openingSpeech): static
    {
        $this->openingSpeech = $openingSpeech;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getIllustrationUrl(): ?string
    {
        return $this->illustrationUrl;
    }

    public function setIllustrationUrl(?string $illustrationUrl): static
    {
        $this->illustrationUrl = $illustrationUrl;
        return $this;
    }

    public function getReminderAt(): ?\DateTimeImmutable
    {
        return $this->reminderAt;
    }

    public function setReminderAt(?\DateTimeImmutable $reminderAt): static
    {
        $this->reminderAt = $reminderAt;
        return $this;
    }

    public function getRegistrationEndAt(): ?\DateTimeImmutable
    {
        return $this->registrationEndAt;
    }

    public function setRegistrationEndAt(?\DateTimeImmutable $registrationEndAt): static
    {
        $this->registrationEndAt = $registrationEndAt;
        return $this;
    }

    public function getReminderSentAt(): ?\DateTimeImmutable
    {
        return $this->reminderSentAt;
    }

    public function setReminderSentAt(?\DateTimeImmutable $reminderSentAt): static
    {
        $this->reminderSentAt = $reminderSentAt;
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

    public function getFaction(): ?Faction
    {
        return $this->faction;
    }

    public function setFaction(?Faction $faction): static
    {
        $this->faction = $faction;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, Character>
     */
    public function getEventCharacters(): Collection
    {
        return $this->eventCharacters;
    }

    public function addEventCharacter(Character $character): static
    {
        if (!$this->eventCharacters->contains($character)) {
            $this->eventCharacters->add($character);
            $character->setEventActivity($this);
        }

        return $this;
    }

    public function removeEventCharacter(Character $character): static
    {
        if ($this->eventCharacters->removeElement($character)) {
            if ($character->getEventActivity() === $this) {
                $character->setEventActivity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAllowedUsers(): Collection
    {
        return $this->allowedUsers;
    }

    public function addAllowedUser(User $user): static
    {
        if (!$this->allowedUsers->contains($user)) {
            $this->allowedUsers->add($user);
        }

        return $this;
    }

    public function removeAllowedUser(User $user): static
    {
        $this->allowedUsers->removeElement($user);

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
            $thread->addRpActivity($this);
        }

        return $this;
    }

    public function removeThread(Thread $thread): static
    {
        if ($this->threads->removeElement($thread)) {
            $thread->removeRpActivity($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, RpActivityRegistration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(RpActivityRegistration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setActivity($this);
        }

        return $this;
    }

    public function removeRegistration(RpActivityRegistration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            if ($registration->getActivity() === $this) {
                $registration->setActivity(null);
            }
        }

        return $this;
    }
}
