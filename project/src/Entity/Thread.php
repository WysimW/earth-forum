<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ThreadRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ThreadRepository::class)]
class Thread
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['forum_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['forum_detail'])]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'threads')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Forum $forum = null;

    #[ORM\ManyToOne(inversedBy: 'threads')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['forum_detail'])]
    private ?User $author = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'thread')]
    private Collection $posts;

    public function __construct()
    {  $this->createdAt = new \DateTimeImmutable();  // Set the default value when the entity is created

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

    public function getForum(): ?Forum
    {
        return $this->forum;
    }

    public function setForum(?Forum $forum): static
    {
        $this->forum = $forum;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

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
            $post->setThread($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getThread() === $this) {
                $post->setThread(null);
            }
        }

        return $this;
    }

    public function getLastPostInfo(): ?array
    {
        $lastPost = $this->posts->last();
    
        if ($lastPost && !$lastPost instanceof Post) {
            return null;
        }
    
        if ($lastPost) {
            return [
                'id' => $lastPost->getId(),
                'title'=> $this->getTitle(),
                'author' => $lastPost->getAuthor()->getPseudo(),
                'date' => $lastPost->getCreatedAt()->format('Y-m-d H:i:s'),
                'excerpt' => substr($lastPost->getContent(), 0, 50),
                'avatar' => $lastPost->getAuthor()->getAvatar(),
            ];
        }
    
        return null;
    }
    
}
