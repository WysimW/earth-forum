<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\Collection;
use App\Repository\CategoriesTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: CategoriesTypeRepository::class)]
#[ApiResource]
class CategoriesType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'type', targetEntity: ForumCategory::class)]
    private Collection $forumCategories;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = "default";

    public function __construct()
    {
        $this->forumCategories = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, ForumCategory>
     */
    public function getForumCategories(): Collection
    {
        return $this->forumCategories;
    }

    public function addForumCategory(ForumCategory $forumCategory): static
    {
        if (!$this->forumCategories->contains($forumCategory)) {
            $this->forumCategories->add($forumCategory);
            $forumCategory->setType($this);
        }

        return $this;
    }

    public function removeForumCategory(ForumCategory $forumCategory): static
    {
        if ($this->forumCategories->removeElement($forumCategory)) {
            // set the owning side to null (unless already changed)
            if ($forumCategory->getType() === $this) {
                $forumCategory->setType(null);
            }
        }

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

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSlug(): void
    {
        if (empty($this->slug) && !empty($this->name)) {
            $slugger = new AsciiSlugger();  
            $this->slug = strtolower($slugger->slug($this->name));
        }
    }

}
