<?php

namespace App\Entity;

use App\Repository\SubForumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubForumRepository::class)]
class SubForum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column]
    private ?bool $isRp = null;

    #[ORM\ManyToOne(inversedBy: 'subForums')]
    private ?Forum $forum = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subForums')]
    private ?self $subforum = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'subforum')]
    private Collection $subForums;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'subForum')]
    private Collection $posts;

    public function __construct()
    {
        $this->subForums = new ArrayCollection();
        $this->posts = new ArrayCollection();
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

    public function isRp(): ?bool
    {
        return $this->isRp;
    }

    public function setRp(bool $isRp): static
    {
        $this->isRp = $isRp;

        return $this;
    }

    public function getForum(): ?Forum
    {
        return $this->forum;
    }

    public function setForum(?Forum $forum): static
    {
        $this->forum = $forum;

        return $this;
    }

    public function getSubforum(): ?self
    {
        return $this->subforum;
    }

    public function setSubforum(?self $subforum): static
    {
        $this->subforum = $subforum;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSubForums(): Collection
    {
        return $this->subForums;
    }

    public function addSubForum(self $subForum): static
    {
        if (!$this->subForums->contains($subForum)) {
            $this->subForums->add($subForum);
            $subForum->setSubforum($this);
        }

        return $this;
    }

    public function removeSubForum(self $subForum): static
    {
        if ($this->subForums->removeElement($subForum)) {
            // set the owning side to null (unless already changed)
            if ($subForum->getSubforum() === $this) {
                $subForum->setSubforum(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setSubForum($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getSubForum() === $this) {
                $post->setSubForum(null);
            }
        }

        return $this;
    }
}
