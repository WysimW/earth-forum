<?php

namespace App\Entity;

use App\Repository\UniversRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;


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

    public function __construct()
    {   
        $this->createdAt = new \DateTimeImmutable();  // Set the default value when the entity is created
        $this->characters = new ArrayCollection();
        $this->npcs = new ArrayCollection();
        $this->forums = new ArrayCollection();
        $this->locations = new ArrayCollection();
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
}
