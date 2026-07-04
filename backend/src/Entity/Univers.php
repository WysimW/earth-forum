<?php

namespace App\Entity;

use App\Repository\UniversRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Messaging\Conversation;


#[ORM\Entity(repositoryClass: UniversRepository::class)]
#[ApiResource]
class Univers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = "default";

    /**
     * @var Collection<int, Character>
     */
    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'universe')]
    private Collection $characters;

    /**
     * @var Collection<int, Npc>
     */
    #[ORM\OneToMany(targetEntity: Npc::class, mappedBy: 'universe')]
    private Collection $npcs;

    /**
     * @var Collection<int, Forum>
     */
    #[ORM\OneToMany(targetEntity: Forum::class, mappedBy: 'universe')]
    private Collection $forums;
    
    /**
     * @var Collection<int, Location>
     */
    #[ORM\OneToMany(targetEntity: Location::class, mappedBy: 'universe')]
    private Collection $locations;

    /**
     * @var Collection<int, Elseworld>
     */
    #[ORM\OneToMany(targetEntity: Elseworld::class, mappedBy: 'parentUniverse')]
    private Collection $elseworlds;

    /**
     * @var Collection<int, Faction>
     */
    #[ORM\OneToMany(targetEntity: Faction::class, mappedBy: 'universe')]
    private Collection $factions;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'universe')]
    private Collection $conversations;

    /**
     * @var Collection<int, RpActivity>
     */
    #[ORM\OneToMany(targetEntity: RpActivity::class, mappedBy: 'universe')]
    private Collection $rpActivities;

    public function __construct()
    {   
        $this->createdAt = new \DateTimeImmutable();  // Set the default value when the entity is created
        $this->characters = new ArrayCollection();
        $this->npcs = new ArrayCollection();
        $this->forums = new ArrayCollection();
        $this->locations = new ArrayCollection();
        $this->elseworlds = new ArrayCollection();
        $this->factions = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->rpActivities = new ArrayCollection();
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

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
            $character->setUniverse($this);
        }

        return $this;
    }

    public function removeCharacter(Character $character): static
    {
        if ($this->characters->removeElement($character)) {
            // set the owning side to null (unless already changed)
            if ($character->getUniverse() === $this) {
                $character->setUniverse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Forum>
     */
    public function getForums(): Collection
    {
        return $this->forums;
    }

    public function addForum(Forum $forum): static
    {
        if (!$this->forums->contains($forum)) {
            $this->forums->add($forum);
            $forum->setUniverse($this);
        }

        return $this;
    }

    public function removeForum(Forum $forum): static
    {
        if ($this->forums->removeElement($forum)) {
            // set the owning side to null (unless already changed)
            if ($forum->getUniverse() === $this) {
                $forum->setUniverse(null);
            }
        }

        return $this;
    }
    
    /**
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }
    
    public function addLocation(Location $location): static
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
            $location->setUniverse($this);
        }
        
        return $this;
    }
    
    public function removeLocation(Location $location): static
    {
        if ($this->locations->removeElement($location)) {
            // set the owning side to null (unless already changed)
            if ($location->getUniverse() === $this) {
                $location->setUniverse(null);
            }
        }
        
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
            $npc->setUniverse($this);
        }

        return $this;
    }

    public function removeNpc(Npc $npc): static
    {
        if ($this->npcs->removeElement($npc)) {
            // set the owning side to null (unless already changed)
            if ($npc->getUniverse() === $this) {
                $npc->setUniverse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Elseworld>
     */
    public function getElseworlds(): Collection
    {
        return $this->elseworlds;
    }

    public function addElseworld(Elseworld $elseworld): static
    {
        if (!$this->elseworlds->contains($elseworld)) {
            $this->elseworlds->add($elseworld);
            $elseworld->setParentUniverse($this);
        }

        return $this;
    }

    public function removeElseworld(Elseworld $elseworld): static
    {
        if ($this->elseworlds->removeElement($elseworld)) {
            // set the owning side to null (unless already changed)
            if ($elseworld->getParentUniverse() === $this) {
                $elseworld->setParentUniverse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Faction>
     */
    public function getFactions(): Collection
    {
        return $this->factions;
    }

    public function addFaction(Faction $faction): static
    {
        if (!$this->factions->contains($faction)) {
            $this->factions->add($faction);
            $faction->setUniverse($this);
        }

        return $this;
    }

    public function removeFaction(Faction $faction): static
    {
        if ($this->factions->removeElement($faction)) {
            // set the owning side to null (unless already changed)
            if ($faction->getUniverse() === $this) {
                $faction->setUniverse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->setUniverse($this);
        }

        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            // set the owning side to null (unless already changed)
            if ($conversation->getUniverse() === $this) {
                $conversation->setUniverse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RpActivity>
     */
    public function getRpActivities(): Collection
    {
        return $this->rpActivities;
    }

    public function addRpActivity(RpActivity $rpActivity): static
    {
        if (!$this->rpActivities->contains($rpActivity)) {
            $this->rpActivities->add($rpActivity);
            $rpActivity->setUniverse($this);
        }

        return $this;
    }

    public function removeRpActivity(RpActivity $rpActivity): static
    {
        if ($this->rpActivities->removeElement($rpActivity)) {
            if ($rpActivity->getUniverse() === $this) {
                $rpActivity->setUniverse(null);
            }
        }

        return $this;
    }
}
