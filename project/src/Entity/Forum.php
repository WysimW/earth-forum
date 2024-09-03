<?php

namespace App\Entity;

use App\Repository\ForumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: ForumRepository::class)]
#[ApiResource]
class Forum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['subforum_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['subforum_detail'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['forum_detail'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'forums')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ForumCategory $category = null;

    #[ORM\ManyToOne(inversedBy: 'forums')]
    private ?Univers $universe = null;

    /**
     * @var Collection<int, Thread>
     */
    #[ORM\OneToMany(targetEntity: Thread::class, mappedBy: 'forum')]
    #[Groups(['forum_detail'])]
    private Collection $threads;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['subforum_detail', 'forum_detail'])]
    private ?string $banner = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subforums')]
    private ?self $forum = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'forum')]
    #[Groups(['forum_detail'])]
    private Collection $subforums;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heroLogo = null;

    public function __construct()
    {   $this->createdAt = new \DateTimeImmutable();  // Set the default value when the entity is created
        $this->updatedAt = new \DateTime();  // Set the default value when the entity is created
        $this->threads = new ArrayCollection();
        $this->subforums = new ArrayCollection();
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

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): static
    {
        $this->banner = $banner;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getForum(): ?self
    {
        return $this->forum;
    }

    public function setForum(?self $forum): static
    {
        $this->forum = $forum;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSubforums(): Collection
    {
        return $this->subforums;
    }

    public function addSubforum(self $subforum): static
    {
        if (!$this->subforums->contains($subforum)) {
            $this->subforums->add($subforum);
            $subforum->setForum($this);
        }

        return $this;
    }

    public function removeSubforum(self $subforum): static
    {
        if ($this->subforums->removeElement($subforum)) {
            // set the owning side to null (unless already changed)
            if ($subforum->getForum() === $this) {
                $subforum->setForum(null);
            }
        }

        return $this;
    }

    public function getLastPostInfo(): ?array
    {
        // Assuming threads are ordered by creation date in ascending order
        $lastThread = $this->threads->last();

        if ($lastThread) {
            return $lastThread->getLastPostInfo(); // Use the method from the Thread entity
        }

        return null;
    }

    public function getHeroLogo(): ?string
    {
        return $this->heroLogo;
    }

    public function setHeroLogo(?string $heroLogo): static
    {
        $this->heroLogo = $heroLogo;

        return $this;
    }

}
