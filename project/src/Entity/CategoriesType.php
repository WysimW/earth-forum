<?php

namespace App\Entity;

use App\Repository\CategoriesTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriesTypeRepository::class)]
class CategoriesType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, ForumCategory>
     */
    #[ORM\OneToMany(targetEntity: ForumCategory::class, mappedBy: 'type')]
    private Collection $forumCategories;

    public function __construct()
    {
        $this->forumCategories = new ArrayCollection();
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
}
