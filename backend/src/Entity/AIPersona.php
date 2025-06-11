<?php

namespace App\Entity;

use App\Repository\AIPersonaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Interface\TimestampableInterface;
use App\Entity\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: AIPersonaRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AIPersona implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json')]
    private array $personalityTraits = [];

    #[ORM\Column(type: 'json')]
    private array $knowledge = [];

    #[ORM\Column(type: 'json')]
    private array $relationships = [];

    #[ORM\Column(type: 'json')]
    private array $goals = [];

    #[ORM\Column(type: 'json')]
    private array $speechPattern = [];

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Character $character = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $postHistory = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $narrativeContext = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    public function __construct()
    {
        $this->postHistory = [];
        $this->personalityTraits = [];
        $this->knowledge = [];
        $this->relationships = [];
        $this->goals = [];
        $this->speechPattern = [];
    }

    // Getters and setters
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

    public function getPersonalityTraits(): array
    {
        return $this->personalityTraits;
    }

    public function setPersonalityTraits(array $personalityTraits): static
    {
        $this->personalityTraits = $personalityTraits;
        return $this;
    }

    public function getKnowledge(): array
    {
        return $this->knowledge;
    }

    public function setKnowledge(array $knowledge): static
    {
        $this->knowledge = $knowledge;
        return $this;
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function setRelationships(array $relationships): static
    {
        $this->relationships = $relationships;
        return $this;
    }

    public function getGoals(): array
    {
        return $this->goals;
    }

    public function setGoals(array $goals): static
    {
        $this->goals = $goals;
        return $this;
    }

    public function getSpeechPattern(): array
    {
        return $this->speechPattern;
    }

    public function setSpeechPattern(array $speechPattern): static
    {
        $this->speechPattern = $speechPattern;
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

    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    public function setCharacter(?Character $character): static
    {
        $this->character = $character;
        return $this;
    }

    public function getPostHistory(): ?array
    {
        return $this->postHistory;
    }

    public function setPostHistory(?array $postHistory): static
    {
        $this->postHistory = $postHistory;
        return $this;
    }

    public function getNarrativeContext(): ?string
    {
        return $this->narrativeContext;
    }

    public function setNarrativeContext(?string $narrativeContext): static
    {
        $this->narrativeContext = $narrativeContext;
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;
        return $this;
    }
} 