<?php

namespace App\Entity;

use App\Repository\FactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Interface\TimestampableInterface;
use App\Entity\Trait\TimestampableTrait;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: FactionRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Faction implements TimestampableInterface
{
    use TimestampableTrait;
    
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'factions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Univers $universe = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $alignment = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $scope = null;

    #[ORM\ManyToMany(targetEntity: Character::class, inversedBy: 'factionsRelation')]
    private Collection $characters;

    #[ORM\ManyToMany(targetEntity: Npc::class, inversedBy: 'factionsRelation')]
    private Collection $npcs;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectives = null;

    #[ORM\ManyToOne]
    private ?Location $headquarters = null;

    #[ORM\ManyToOne(inversedBy: 'createdFactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $founder = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = "default";

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $headquartersDescription = null;

    #[ORM\ManyToOne]
    private ?Forum $subforum = null;

    #[ORM\ManyToMany(targetEntity: Thread::class)]
    private Collection $scenes;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    public function __construct()
    {
        $this->characters = new ArrayCollection();
        $this->npcs = new ArrayCollection();
        $this->scenes = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getAlignment(): ?string
    {
        return $this->alignment;
    }

    public function setAlignment(?string $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return Collection<int, Character>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function addCharacter(Character $character): static
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
        }

        return $this;
    }

    public function removeCharacter(Character $character): static
    {
        $this->characters->removeElement($character);

        return $this;
    }

    /**
     * @return Collection<int, Npc>
     */
    public function getNpcs(): Collection
    {
        return $this->npcs;
    }

    public function addNpc(Npc $npc): static
    {
        if (!$this->npcs->contains($npc)) {
            $this->npcs->add($npc);
        }

        return $this;
    }

    public function removeNpc(Npc $npc): static
    {
        $this->npcs->removeElement($npc);

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

    public function getObjectives(): ?string
    {
        return $this->objectives;
    }

    public function setObjectives(?string $objectives): static
    {
        $this->objectives = $objectives;

        return $this;
    }

    public function getHeadquarters(): ?Location
    {
        return $this->headquarters;
    }

    public function setHeadquarters(?Location $headquarters): static
    {
        $this->headquarters = $headquarters;

        return $this;
    }

    public function getFounder(): ?User
    {
        return $this->founder;
    }

    public function setFounder(?User $founder): static
    {
        $this->founder = $founder;

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

    public function getHeadquartersDescription(): ?string
    {
        return $this->headquartersDescription;
    }

    public function setHeadquartersDescription(?string $headquartersDescription): static
    {
        $this->headquartersDescription = $headquartersDescription;

        return $this;
    }

    public function getSubforum(): ?Forum
    {
        return $this->subforum;
    }

    public function setSubforum(?Forum $subforum): static
    {
        $this->subforum = $subforum;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        
        return $this;
    }

    /**
     * @return Collection<int, Thread>
     */
    public function getScenes(): Collection
    {
        return $this->scenes;
    }

    public function addScene(Thread $scene): static
    {
        if (!$this->scenes->contains($scene)) {
            $this->scenes->add($scene);
        }

        return $this;
    }

    public function removeScene(Thread $scene): static
    {
        $this->scenes->removeElement($scene);

        return $this;
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new \DateTimeImmutable());
        }
        $this->updateSlug();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->setUpdatedAt(new \DateTimeImmutable());
        $this->updateSlug();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSlug(): void
    {
        if ($this->name) {
            $slugger = new AsciiSlugger();
            $this->slug = strtolower($slugger->slug($this->name));
        }
    }
} 