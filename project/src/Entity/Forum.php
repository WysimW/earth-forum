<?php

namespace App\Entity;

use App\Repository\ForumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumRepository::class)]
class Forum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'forums')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ForumCategory $category = null;

    #[ORM\ManyToOne(inversedBy: 'forums')]
    private ?Univers $universe = null;

    /**
     * @var Collection<int, Thread>
     */
    #[ORM\OneToMany(targetEntity: Thread::class, mappedBy: 'forum')]
    private Collection $threads;

    /**
     * @var Collection<int, SubForum>
     */
    #[ORM\OneToMany(targetEntity: SubForum::class, mappedBy: 'forum')]
    private Collection $subForums;

    public function __construct()
    {
        $this->threads = new ArrayCollection();
        $this->subForums = new ArrayCollection();
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

    public function getCategory(): ?ForumCategory
    {
        return $this->category;
    }

    public function setCategory(?ForumCategory $category): static
    {
        $this->category = $category;

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
            $thread->setForum($this);
        }

        return $this;
    }

    public function removeThread(Thread $thread): static
    {
        if ($this->threads->removeElement($thread)) {
            // set the owning side to null (unless already changed)
            if ($thread->getForum() === $this) {
                $thread->setForum(null);
            }
        }

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
