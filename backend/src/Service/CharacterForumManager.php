<?php

namespace App\Service;

use App\Entity\Forum;
use App\Entity\ForumCategory;
use App\Repository\ForumCategoryRepository;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CharacterForumManager
{
    private EntityManagerInterface $entityManager;
    private ForumRepository $forumRepository;
    private ForumCategoryRepository $categoryRepository;
    private SluggerInterface $slugger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ForumRepository $forumRepository,
        ForumCategoryRepository $categoryRepository,
        SluggerInterface $slugger
    ) {
        $this->entityManager = $entityManager;
        $this->forumRepository = $forumRepository;
        $this->categoryRepository = $categoryRepository;
        $this->slugger = $slugger;
    }

    private function generateUniqueSlug(string $name, string $entityClass): string
    {
        $baseSlug = $this->slugger->slug(strtolower($name))->lower();
        $slug = $baseSlug;
        $counter = 1;

        while ($this->entityManager->getRepository($entityClass)->findOneBy(['slug' => $slug])) {
            $slug = sprintf('%s-%d', $baseSlug, $counter++);
        }

        return $slug;
    }

    private function updateExistingForums(): void
    {
        $forums = $this->forumRepository->findAll();
        foreach ($forums as $forum) {
            if (!$forum->getSlug()) {
                $forum->setSlug($this->generateUniqueSlug($forum->getName(), Forum::class));
            }
        }
        $this->entityManager->flush();
    }

    private function getCharacterForumCategory(): ForumCategory
    {
        $categorySlug = $this->generateUniqueSlug('personnages', ForumCategory::class);
        $category = $this->categoryRepository->findOneBy(['slug' => $categorySlug]);
        
        if (!$category) {
            $category = new ForumCategory();
            $category->setName('Personnages');
            $category->setDescription('Création et gestion des personnages');
            $category->setPosition(0);
            $category->setSlug($categorySlug);
            
            $this->entityManager->persist($category);
            $this->entityManager->flush();
        }
        
        return $category;
    }

    private function getOrCreateCharacterForum(string $name, string $description, int $position): Forum
    {
        $slug = $this->generateUniqueSlug($name, Forum::class);
        $forum = $this->forumRepository->findOneBy(['slug' => $slug]);
        
        if (!$forum) {
            $forum = new Forum();
            $forum->setName($name);
            $forum->setDescription($description);
            $forum->setPosition($position);
            $forum->setSlug($slug);
            $forum->setCategory($this->getCharacterForumCategory());
            $forum->setStatus('open');
            
            $this->entityManager->persist($forum);
            $this->entityManager->flush();
        }
        
        return $forum;
    }

    public function ensureCharacterForumsExist(): array
    {
        $forums = [];
        
        $forums['pending'] = $this->getOrCreateCharacterForum(
            'Fiches en attente',
            'Fiches de personnages en cours de création ou en attente de validation',
            0
        );
        
        $forums['validated'] = $this->getOrCreateCharacterForum(
            'Fiches validées',
            'Fiches de personnages validées',
            1
        );
        
        $forums['rejected'] = $this->getOrCreateCharacterForum(
            'Fiches refusées',
            'Fiches de personnages refusées ou abandonnées',
            2
        );
        
        return $forums;
    }
}