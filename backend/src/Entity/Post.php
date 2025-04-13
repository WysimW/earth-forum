<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Interface\TimestampableInterface;
use App\Entity\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Post implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Thread $thread = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $author = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $editedAt = null;
    
    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Character $character = null;

    #[ORM\Column(length: 20)]
    private string $type = 'normal'; // normal, roleplay, announcement, ic (in-character), ooc (out-of-character), etc.
    
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDraft = false;

    public function __construct()
    {  
        $this->createdAt = new \DateTimeImmutable(); // Set the default value for createdAt
        $this->updatedAt = new \DateTimeImmutable();  // Set the default value for updatedAt as well
    }

    public function isRoleplay(): bool
    {
        return $this->type === 'roleplay';
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    public function setThread(?Thread $thread): static
    {
        $this->thread = $thread;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }
    
    public function getCharacter(): ?Character
    {
        return $this->character;
    }
    
    public function setCharacter(?Character $character): static
    {
        $this->character = $character;
        
        return $this;
    }
    
    public function isInCharacter(): bool
    {
        return $this->type === 'ic';
    }
    
    public function isOutOfCharacter(): bool
    {
        return $this->type === 'ooc';
    }

    /**
     * Get the value of type
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */ 
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of editedAt
     */ 
    public function getEditedAt()
    {
        return $this->editedAt;
    }

    /**
     * Set the value of editedAt
     *
     * @return  self
     */ 
    public function setEditedAt($editedAt)
    {
        $this->editedAt = $editedAt;

        return $this;
    }

    public function isDraft(): bool
    {
        return $this->isDraft;
    }
    
    public function setIsDraft(bool $isDraft): self
    {
        $this->isDraft = $isDraft;
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
