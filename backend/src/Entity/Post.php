<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Interface\TimestampableInterface;
use App\Entity\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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

    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Post $quotedPost = null;

    #[ORM\ManyToMany(targetEntity: Npc::class)]
    #[ORM\JoinTable(name: 'post_npcs')]
    private Collection $npcs;
    
    /**
     * @var Collection<int, ReadPost>
     */
    #[ORM\OneToMany(targetEntity: ReadPost::class, mappedBy: 'post', orphanRemoval: true)]
    private Collection $readBy;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $originalContent = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isHidden = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moderationReason = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $moderator = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $hiddenAt = null;

    public function __construct()
    {  
        $this->createdAt = new \DateTimeImmutable(); // Set the default value for createdAt
        $this->updatedAt = new \DateTimeImmutable();  // Set the default value for updatedAt as well
        $this->npcs = new ArrayCollection();
        $this->readBy = new ArrayCollection();
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
        return $this->type === 'roleplay';
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

    public function getQuotedPost(): ?Post
    {
        return $this->quotedPost;
    }

    public function setQuotedPost(?Post $quotedPost): self
    {
        $this->quotedPost = $quotedPost;
        return $this;
    }

    /**
     * @return Collection<int, Npc>
     */
    public function getNpcs(): Collection
    {
        return $this->npcs;
    }

    public function addNpc(Npc $npc): self
    {
        if (!$this->npcs->contains($npc)) {
            $this->npcs->add($npc);
        }

        return $this;
    }

    public function removeNpc(Npc $npc): self
    {
        $this->npcs->removeElement($npc);

        return $this;
    }

    /**
     * @return Collection<int, ReadPost>
     */
    public function getReadBy(): Collection
    {
        return $this->readBy;
    }

    public function addReadBy(ReadPost $readPost): static
    {
        if (!$this->readBy->contains($readPost)) {
            $this->readBy->add($readPost);
            $readPost->setPost($this);
        }

        return $this;
    }

    public function removeReadBy(ReadPost $readPost): static
    {
        if ($this->readBy->removeElement($readPost)) {
            // set the owning side to null (unless already changed)
            if ($readPost->getPost() === $this) {
                $readPost->setPost(null);
            }
        }

        return $this;
    }

    public function isReadByUser(User $user): bool
    {
        foreach ($this->readBy as $readPost) {
            if ($readPost->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    public function getOriginalContent(): ?string
    {
        return $this->originalContent;
    }

    public function setOriginalContent(?string $originalContent): self
    {
        $this->originalContent = $originalContent;
        return $this;
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(bool $isHidden): self
    {
        $this->isHidden = $isHidden;
        return $this;
    }

    public function getModerationReason(): ?string
    {
        return $this->moderationReason;
    }

    public function setModerationReason(?string $moderationReason): self
    {
        $this->moderationReason = $moderationReason;
        return $this;
    }

    public function getModerator(): ?User
    {
        return $this->moderator;
    }

    public function setModerator(?User $moderator): self
    {
        $this->moderator = $moderator;
        return $this;
    }

    public function getHiddenAt(): ?\DateTimeInterface
    {
        return $this->hiddenAt;
    }

    public function setHiddenAt(?\DateTimeInterface $hiddenAt): self
    {
        $this->hiddenAt = $hiddenAt;
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
