<?php

namespace App\Entity;

use App\Repository\ReadPostRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Interface\TimestampableInterface;
use App\Entity\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: ReadPostRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'user_post_unique', columns: ['user_id', 'post_id'])]
class ReadPost implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'readPosts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->initializeTimestamps();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updateTimestamps();
    }
} 