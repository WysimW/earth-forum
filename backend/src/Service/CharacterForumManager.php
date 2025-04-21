<?php

namespace App\Service;

use App\Entity\Forum;
use App\Entity\Univers;
use App\Entity\ForumCategory;
use App\Repository\ForumCategoryRepository;
use App\Repository\ForumRepository;
use App\Repository\UniversRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CharacterForumManager
{
    private EntityManagerInterface $entityManager;
    private ForumRepository $forumRepository;
    private ForumCategoryRepository $categoryRepository;
    private UniversRepository $universRepository;
    private SluggerInterface $slugger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ForumRepository $forumRepository,
        ForumCategoryRepository $categoryRepository,
        UniversRepository $universRepository,
        SluggerInterface $slugger
    ) {
        $this->entityManager = $entityManager;
        $this->forumRepository = $forumRepository;
        $this->categoryRepository = $categoryRepository;
        $this->universRepository = $universRepository;
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
        // Chercher d'abord par nom plutôt que par slug
        $category = $this->categoryRepository->findOneBy(['name' => 'Personnages']);
        
        if (!$category) {
            // Vérifier aussi par slug
            $categorySlug = $this->generateUniqueSlug('personnages', ForumCategory::class);
            $category = $this->categoryRepository->findOneBy(['slug' => $categorySlug]);
            
            if (!$category) {
                // Créer seulement si la catégorie n'existe vraiment pas
                $category = new ForumCategory();
                $category->setName('Personnages');
                $category->setDescription('Création et gestion des personnages');
                $category->setPosition(0);
                $category->setSlug($categorySlug);
                
                $this->entityManager->persist($category);
                $this->entityManager->flush();
            }
        }
        
        return $category;
    }
    
    private function getOrCreatePresentationForum(?Univers $universe = null): Forum
    {
        // Chercher d'abord par nom et univers si spécifié
        $criteria = ['name' => 'Présentation'];
        if ($universe) {
            $criteria['universe'] = $universe;
        }
        
        $forum = $this->forumRepository->findOneBy($criteria);
        
        if (!$forum) {
            $slug = $this->generateUniqueSlug('presentation' . ($universe ? '-' . $universe->getSlug() : ''), Forum::class);
            $forum = new Forum();
            $forum->setName('Présentation');
            $forum->setDescription('Forum de présentation des personnages' . ($universe ? ' de l\'univers ' . $universe->getName() : ''));
            $forum->setPosition(0);
            $forum->setSlug($slug);
            $forum->setCategory($this->getCharacterForumCategory());
            $forum->setStatus('open');
            
            if ($universe) {
                $forum->setUniverse($universe);
            }
            
            $this->entityManager->persist($forum);
            $this->entityManager->flush();
        }
        
        return $forum;
    }

    private function getOrCreateCharacterSubForum(string $name, string $description, int $position, Forum $parentForum, ?Univers $universe = null): Forum
    {
        // Chercher d'abord par nom, parent et univers si spécifié
        $criteria = ['name' => $name, 'parent' => $parentForum];
        if ($universe) {
            $criteria['universe'] = $universe;
        }
        
        $forum = $this->forumRepository->findOneBy($criteria);
        
        if (!$forum) {
            $suffix = $universe ? '-' . $universe->getSlug() : '';
            $slug = $this->generateUniqueSlug($name . $suffix, Forum::class);
            $forum = new Forum();
            $forum->setName($name);
            $forum->setDescription($description . ($universe ? ' de l\'univers ' . $universe->getName() : ''));
            $forum->setPosition($position);
            $forum->setSlug($slug);
            $forum->setParent($parentForum);
            $forum->setStatus('open');
            
            if ($universe) {
                $forum->setUniverse($universe);
            }
            
            $this->entityManager->persist($forum);
            $this->entityManager->flush();
        } else if ($forum->getParent() !== $parentForum) {
            // Si le forum existe mais n'a pas le bon parent, mettre à jour le parent
            $forum->setParent($parentForum);
            $this->entityManager->flush();
        }
        
        return $forum;
    }

    /**
     * Ensure character forums exist for a specific universe
     * @param Univers|null $universe The universe to create forums for
     * @return array The forums created or found
     */
    public function ensureCharacterForumsExistForUniverse(?Univers $universe = null): array
    {
        $forums = [];
        
        // Créer ou récupérer le forum principal de présentation pour cet univers
        $presentationForum = $this->getOrCreatePresentationForum($universe);
        
        // Créer ou récupérer les sous-forums
        $forums['pending'] = $this->getOrCreateCharacterSubForum(
            'Fiches en attente',
            'Fiches de personnages en cours de création ou en attente de validation',
            0,
            $presentationForum,
            $universe
        );
        
        $forums['validated'] = $this->getOrCreateCharacterSubForum(
            'Fiches validées',
            'Fiches de personnages validées',
            1,
            $presentationForum,
            $universe
        );
        
        $forums['rejected'] = $this->getOrCreateCharacterSubForum(
            'Fiches refusées',
            'Fiches de personnages refusées ou abandonnées',
            2,
            $presentationForum,
            $universe
        );
        
        return $forums;
    }

    /**
     * Méthode legacy, garde pour compatibilité
     * @deprecated Use ensureCharacterForumsExistForUniverse() instead
     */
    public function ensureCharacterForumsExist(): array
    {
        return $this->ensureCharacterForumsExistForUniverse();
    }
}