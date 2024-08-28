<?php

namespace App\Entity;

use App\Repository\ForumRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumRepository::class)]
#[ApiResource]
class Forum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'forums')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Universe $universe = null;

    /**
     * @var Collection<int, SubForum>
     */
    #[ORM\OneToMany(targetEntity: SubForum::class, mappedBy: 'forum')]
    private Collection $subForums;

    public function __construct()
    {
        $this->subForums = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    /**
     * @return Collection<int, SubForum>
     */
    public function getSubForums(): Collection
    {
        return $this->subForums;
    }

    public function addSubForum(SubForum $subForum): static
    {
        if (!$this->subForums->contains($subForum)) {
            $this->subForums->add($subForum);
            $subForum->setForum($this);
        }

        return $this;
    }

    public function removeSubForum(SubForum $subForum): static
    {
        if ($this->subForums->removeElement($subForum)) {
            // set the owning side to null (unless already changed)
            if ($subForum->getForum() === $this) {
                $subForum->setForum(null);
            }
        }

        return $this;
    }
}
