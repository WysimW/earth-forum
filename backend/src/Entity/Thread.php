<?php

namespace App\Entity;

use App\Entity\Univers;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ThreadRepository;
use App\Entity\Trait\TimestampableTrait;
use Doctrine\Common\Collections\Collection;
use App\Entity\Interface\TimestampableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ThreadRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Thread implements TimestampableInterface
{
    use TimestampableTrait;

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
    private ?Univers $universe = null;

    #[ORM\ManyToOne(inversedBy: 'threads')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['forum_detail'])]
    private ?User $author = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column(length: 20)]
    private string $status = 'open'; // open, closed, archived

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $firstPostContent = null;

    #[ORM\ManyToOne(inversedBy: 'threads')]
    private ?Character $characterCreator = null;

    #[ORM\ManyToOne(inversedBy: 'characterSheetThread')]
    private ?Character $characterSheet = null;

    #[ORM\ManyToOne(inversedBy: 'threads')]
    private ?Location $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tags = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\Column(nullable: true)]
    private ?bool $sticky = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $visibleToCharactersOnly = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDraft = false;

    #[ORM\Column(length: 255)]
    private ?string $slug = "default";

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'thread')]
    private Collection $posts;

    #[ORM\ManyToMany(targetEntity: Character::class)]
    #[ORM\JoinTable(name: 'thread_participants')]
    private Collection $participants;

    #[ORM\ManyToMany(targetEntity: Npc::class)]
    #[ORM\JoinTable(name: 'thread_npcs')]
    private Collection $npcs;

    #[ORM\ManyToMany(targetEntity: Faction::class, inversedBy: 'scenes')]
    #[ORM\JoinTable(name: 'thread_factions')]
    private Collection $factions;

    public function __construct()
    {  
        $this->posts = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->npcs = new ArrayCollection();
        $this->factions = new ArrayCollection();
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

    public function getUniverse(): ?Univers
    {
        return $this->universe;
    }

    public function setUniverse(?Univers $universe): static
    {
        $this->universe = $universe;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
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
    
    public function getFirstPostContent(): ?string
    {
        return $this->firstPostContent;
    }
    
    public function setFirstPostContent(?string $firstPostContent): static
    {
        $this->firstPostContent = $firstPostContent;
        
        return $this;
    }

    public function getCharacterCreator(): ?Character
    {
        return $this->characterCreator;
    }

    public function setCharacterCreator(?Character $characterCreator): static
    {
        $this->characterCreator = $characterCreator;
        
        return $this;
    }

    public function getCharacterSheet(): ?Character
    {
        return $this->characterSheet;
    }

    public function setCharacterSheet(?Character $character): static
    {
        $this->characterSheet = $character;
        
        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;
        
        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(?string $tags): static
    {
        $this->tags = $tags;
        
        return $this;
    }

    public function getTagsArray(): array
    {
        return $this->tags ? explode(',', $this->tags) : [];
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
        
        return $this;
    }

    public function getSticky(): ?bool
    {
        return $this->sticky;
    }

    public function setSticky(?bool $sticky): static
    {
        $this->sticky = $sticky;

        return $this;
    }

    public function isVisibleToCharactersOnly(): bool
    {
        return $this->visibleToCharactersOnly;
    }

    public function setVisibleToCharactersOnly(bool $visibleToCharactersOnly): self
    {
        $this->visibleToCharactersOnly = $visibleToCharactersOnly;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * Retourne le nombre total de posts dans ce thread
     * 
     * @return int
     */
    public function getPostsCount(): int
    {
        return $this->posts->count();
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

    /**
     * @return Collection<int, Character>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Character $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(Character $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * @return Collection<int, Npc>
     */
    public function getNpcs(): Collection
    {
        return $this->npcs;
    }

    public function addNpc(Npc $npc): static
    {
        if (!$this->npcs->contains($npc)) {
            $this->npcs->add($npc);
        }

        return $this;
    }

    public function removeNpc(Npc $npc): static
    {
        $this->npcs->removeElement($npc);

        return $this;
    }

    /**
     * @return Collection<int, Faction>
     */
    public function getFactions(): Collection
    {
        return $this->factions;
    }

    public function addFaction(Faction $faction): static
    {
        if (!$this->factions->contains($faction)) {
            $this->factions->add($faction);
            $faction->addScene($this);
        }

        return $this;
    }

    public function removeFaction(Faction $faction): static
    {
        if ($this->factions->removeElement($faction)) {
            $faction->removeScene($this);
        }

        return $this;
    }

    public function isRoleplay(): bool
    {
        return $this->type === 'roleplay';
    }

    public function isCharacterSheet(): bool
    {
        return $this->type === 'character_sheet';
    }

    public function isFull(): bool
    {
        return $this->maxParticipants !== null && $this->participants->count() >= $this->maxParticipants;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function getLastPostInfo(): ?array
    {
        $lastPost = $this->posts->last();
    
        if ($lastPost && !$lastPost instanceof Post) {
            return null;
        }
    
        if ($lastPost) {
            // Check if the post was made by a character
            if ($lastPost->getCharacter()) {
                return [
                    'id' => $lastPost->getId(),
                    'title' => $this->getTitle(),
                    'author' => $lastPost->getCharacter()->getName(),
                    'date' => $lastPost->getCreatedAt()->format('Y-m-d H:i:s'),
                    'excerpt' => substr($lastPost->getContent(), 0, 50),
                    'avatar' => $lastPost->getCharacter()->getAvatar() ?: $lastPost->getAuthor()->getAvatar(),
                    'isCharacter' => true,
                    'characterId' => $lastPost->getCharacter()->getId(),
                    'character' => $lastPost->getCharacter()->getName() // Add the character name
                ];
            } else {
                return [
                    'id' => $lastPost->getId(),
                    'title' => $this->getTitle(),
                    'author' => $lastPost->getAuthor() ? $lastPost->getAuthor()->getPseudo() : 'Anonymous',
                    'date' => $lastPost->getCreatedAt()->format('Y-m-d H:i:s'),
                    'excerpt' => substr($lastPost->getContent(), 0, 50),
                    'avatar' => $lastPost->getAuthor() ? $lastPost->getAuthor()->getAvatar() : null,
                    'isCharacter' => false,
                    'character' => null // Add a null character for consistency
                ];
            }
        }
    
        return null;
    }

    public function isSticky(): bool
    {
        return $this->sticky === true;
    }

    public function isDraft(): bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): static
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
