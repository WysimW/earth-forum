<?php

namespace App\Entity;

use App\Repository\UniverseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UniverseRepository::class)]
class Universe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Forum>
     */
    #[ORM\OneToMany(targetEntity: Forum::class, mappedBy: 'universe')]
    private Collection $forums;

    /**
     * @var Collection<int, Character>
     */
    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'universe')]
    private Collection $characters;

    public function __construct()
    {
        $this->forums = new ArrayCollection();
        $this->characters = new ArrayCollection();
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
}
