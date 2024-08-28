<?php

namespace App\Entity;

use App\Repository\CharacterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '`character`')]
class Character
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'characters')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'characters')]
    private ?Universe $universe = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUniverse(): ?Universe
    {
        return $this->universe;
    }

    public function setUniverse(?Universe $universe): static
    {
        $this->universe = $universe;

        return $this;
    }
}
