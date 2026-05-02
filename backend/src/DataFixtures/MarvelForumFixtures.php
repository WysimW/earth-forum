<?php

namespace App\DataFixtures;

use App\Entity\Forum;
use App\Entity\Univers;
use App\Entity\ForumCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class MarvelForumFixtures extends Fixture implements FixtureGroupInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        // Récupérer l'univers Marvel depuis la base de données
        $marvelUniverse = $manager->getRepository(Univers::class)->findOneBy(['slug' => 'marvel']);
        
        if (!$marvelUniverse) {
            throw new \Exception("L'univers Marvel n'existe pas dans la base de données. Assurez-vous que UniverseFixtures est chargé avant.");
        }
        
        // Ajouter une référence pour l'univers Marvel si elle n'existe pas déjà
        try {
            $this->getReference('universe_marvel');
        } catch (\OutOfBoundsException $e) {
            $this->addReference('universe_marvel', $marvelUniverse);
        }
        
        // Récupérer les catégories depuis la base de données
        $categoryRepository = $manager->getRepository(ForumCategory::class);
        $categoryTerre = $categoryRepository->findOneBy(['slug' => 'terre']);
        $categoryUnivers = $categoryRepository->findOneBy(['slug' => 'univers']);
        $categoryInformations = $categoryRepository->findOneBy(['slug' => 'informations']);
        
        if (!$categoryTerre || !$categoryUnivers || !$categoryInformations) {
            throw new \Exception("Les catégories de forums n'existent pas dans la base de données. Assurez-vous que ForumCategoryFixtures est chargé avant.");
        }
        
        // Ajouter des références pour les catégories si elles n'existent pas déjà
        try {
            $this->getReference('category_terre');
        } catch (\OutOfBoundsException $e) {
            $this->addReference('category_terre', $categoryTerre);
        }
        try {
            $this->getReference('category_univers');
        } catch (\OutOfBoundsException $e) {
            $this->addReference('category_univers', $categoryUnivers);
        }
        try {
            $this->getReference('category_informations');
        } catch (\OutOfBoundsException $e) {
            $this->addReference('category_informations', $categoryInformations);
        }
        
        $forums = [
            // Forums de la catégorie Terre - Villes Marvel
            [
                'name' => 'New York',
                'description' => 'La ville qui ne dort jamais, foyer de nombreux super-héros Marvel',
                'banner' => 'https://via.placeholder.com/800x200?text=New+York',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=NY',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
            // Sous-forums de New York
            [
                'name' => 'Avengers Tower',
                'description' => 'Le quartier général des Vengeurs au cœur de Manhattan',
                'banner' => 'https://via.placeholder.com/800x200?text=Avengers+Tower',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=AT',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_new_york',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Baxter Building',
                'description' => 'Le siège des Quatre Fantastiques',
                'banner' => 'https://via.placeholder.com/800x200?text=Baxter+Building',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=BB',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_new_york',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Hell\'s Kitchen',
                'description' => 'Le quartier de Daredevil, où la justice se fait dans l\'ombre',
                'banner' => 'https://via.placeholder.com/800x200?text=Hells+Kitchen',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=HK',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => 'forum_new_york',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Queens',
                'description' => 'Le quartier de Spider-Man, où vit Peter Parker',
                'banner' => 'https://via.placeholder.com/800x200?text=Queens',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=QNS',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 4,
                'parent' => 'forum_new_york',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Stark Industries',
                'description' => 'L\'entreprise de Tony Stark, leader technologique mondial',
                'banner' => 'https://via.placeholder.com/800x200?text=Stark+Industries',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=SI',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 5,
                'parent' => 'forum_new_york',
                'universe' => 'universe_marvel'
            ],
            // Wakanda
            [
                'name' => 'Wakanda',
                'description' => 'Le royaume africain le plus avancé technologiquement, foyer de Black Panther',
                'banner' => 'https://via.placeholder.com/800x200?text=Wakanda',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=WK',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
            // Sous-forums de Wakanda
            [
                'name' => 'Birnin Zana',
                'description' => 'La capitale de Wakanda, ville futuriste cachée au monde',
                'banner' => 'https://via.placeholder.com/800x200?text=Birnin+Zana',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=BZ',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_wakanda',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Montagnes du Mena Ngai',
                'description' => 'Les montagnes sacrées où pousse le vibranium',
                'banner' => 'https://via.placeholder.com/800x200?text=Mena+Ngai',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=MN',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_wakanda',
                'universe' => 'universe_marvel'
            ],
            // Organisations de héros
            [
                'name' => 'Les Vengeurs',
                'description' => 'L\'équipe de super-héros la plus puissante de la Terre',
                'banner' => 'https://via.placeholder.com/800x200?text=Avengers',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=AVG',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
            // Sous-forums des Vengeurs
            [
                'name' => 'Quartier Général des Vengeurs',
                'description' => 'Le QG principal où se réunissent les Vengeurs',
                'banner' => 'https://via.placeholder.com/800x200?text=Avengers+HQ',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=HQ',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_les_vengeurs',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Compound des Vengeurs',
                'description' => 'La base secondaire des Vengeurs',
                'banner' => 'https://via.placeholder.com/800x200?text=Avengers+Compound',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=AC',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_les_vengeurs',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Les Quatre Fantastiques',
                'description' => 'L\'équipe de super-héros explorateurs et scientifiques',
                'banner' => 'https://via.placeholder.com/800x200?text=Fantastic+Four',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=FF',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 4,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'X-Men',
                'description' => 'L\'école pour mutants dirigée par le Professeur Xavier',
                'banner' => 'https://via.placeholder.com/800x200?text=X-Men',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=XM',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 5,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
            // Sous-forums X-Men
            [
                'name' => 'Institut Xavier',
                'description' => 'L\'école pour jeunes mutants, foyer des X-Men',
                'banner' => 'https://via.placeholder.com/800x200?text=Xavier+Institute',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=XI',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_x_men',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Salle de Danger',
                'description' => 'La salle d\'entraînement holographique des X-Men',
                'banner' => 'https://via.placeholder.com/800x200?text=Danger+Room',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=DR',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_x_men',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Genosha',
                'description' => 'L\'île-nation pour mutants, refuge controversé',
                'banner' => 'https://via.placeholder.com/800x200?text=Genosha',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=GN',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => 'forum_x_men',
                'universe' => 'universe_marvel'
            ],
            // Organisations de vilains
            [
                'name' => 'Organisations Criminelles',
                'description' => 'Les organisations de super-vilains et leurs repaires',
                'banner' => 'https://via.placeholder.com/800x200?text=Supervillains',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=SV',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 6,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
            // Sous-forums Organisations Criminelles
            [
                'name' => 'Hydra',
                'description' => 'L\'organisation terroriste secrète qui cherche la domination mondiale',
                'banner' => 'https://via.placeholder.com/800x200?text=Hydra',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=HY',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_organisations_criminelles',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'A.I.M.',
                'description' => 'Advanced Idea Mechanics, organisation scientifique criminelle',
                'banner' => 'https://via.placeholder.com/800x200?text=AIM',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=AIM',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_organisations_criminelles',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'La Main',
                'description' => 'L\'organisation ninja immortelle dirigée par le Poing',
                'banner' => 'https://via.placeholder.com/800x200?text=The+Hand',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=TH',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => 'forum_organisations_criminelles',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Les Sinistres Six',
                'description' => 'L\'alliance de super-vilains opposée à Spider-Man',
                'banner' => 'https://via.placeholder.com/800x200?text=Sinister+Six',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=S6',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 4,
                'parent' => 'forum_organisations_criminelles',
                'universe' => 'universe_marvel'
            ],
            // Forums de la catégorie Univers
            [
                'name' => 'Asgard',
                'description' => 'Le royaume des dieux nordiques, foyer de Thor et Loki',
                'banner' => 'https://via.placeholder.com/800x200?text=Asgard',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=ASG',
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
            // Sous-forums d'Asgard
            [
                'name' => 'Palais d\'Odin',
                'description' => 'Le siège du pouvoir d\'Asgard, résidence de la famille royale',
                'banner' => 'https://via.placeholder.com/800x200?text=Odin+Palace',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=OP',
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_asgard',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Bifröst',
                'description' => 'Le pont arc-en-ciel qui relie Asgard aux Neuf Royaumes',
                'banner' => 'https://via.placeholder.com/800x200?text=Bifrost',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=BF',
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_asgard',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Jotunheim',
                'description' => 'Le royaume des géants de glace, ennemis d\'Asgard',
                'banner' => 'https://via.placeholder.com/800x200?text=Jotunheim',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=JT',
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => 'forum_asgard',
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Sokovia',
                'description' => 'Le pays d\'Europe de l\'Est, foyer des Maximoff',
                'banner' => 'https://via.placeholder.com/800x200?text=Sokovia',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=SK',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
            [
                'name' => 'Latvérie',
                'description' => 'Le petit pays européen dirigé par le Docteur Doom',
                'banner' => 'https://via.placeholder.com/800x200?text=Latveria',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=LV',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 4,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
            // Sous-forums Latvérie
            [
                'name' => 'Château Doom',
                'description' => 'La forteresse du Docteur Doom, siège du pouvoir en Latvérie',
                'banner' => 'https://via.placeholder.com/800x200?text=Doom+Castle',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=DC',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_latverie', // Latvérie normalisé
                'universe' => 'universe_marvel'
            ],
            // Forums HRP - Informations (partagés avec DC)
            [
                'name' => 'Actualités Marvel',
                'description' => 'Discussions sur les comics, films et séries Marvel',
                'banner' => 'https://via.placeholder.com/800x200?text=Marvel+News',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=MN',
                'category' => 'category_informations',
                'isRoleplay' => false,
                'type' => 'hrp',
                'position' => 2,
                'parent' => null,
                'universe' => 'universe_marvel'
            ],
        ];

        // Première passe pour créer tous les forums principaux
        foreach ($forums as $key => $forumData) {
            if ($forumData['parent'] === null) {
                $forum = $this->createForum($forumData, $manager);
                
                // Générer la référence pour les forums parents en utilisant le slugger pour normaliser
                $normalizedName = $this->slugger->slug($forumData['name'])->lower()->toString();
                $refName = 'forum_' . str_replace('-', '_', $normalizedName);
                $this->addReference($refName, $forum);
            }
        }
        
        // Deuxième passe pour les sous-forums qui dépendent des forums parents
        foreach ($forums as $forumData) {
            if ($forumData['parent'] !== null) {
                $subforum = $this->createForum($forumData, $manager);
                
                // Créer une référence unique pour chaque sous-forum en incluant le parent
                $refName = 'subforum_' . strtolower(str_replace([' ', '&', '\'', '-'], '_', $forumData['name']));
                $this->addReference($refName, $subforum);
            }
        }

        $manager->flush();
    }

    private function createForum(array $forumData, ObjectManager $manager): Forum
    {
        $forum = new Forum();
        $forum->setName($forumData['name']);
        $forum->setDescription($forumData['description']);
        $forum->setBanner($forumData['banner']);
        $forum->setHeroLogo($forumData['heroLogo']);
        $forum->setCategory($this->getReference($forumData['category']));
        $forum->setIsRoleplay($forumData['isRoleplay']);
        $forum->setType($forumData['type']);
        $forum->setPosition($forumData['position']);
        $forum->setSlug($this->slugger->slug($forumData['name'])->lower());
        
        // Ajouter l'univers Marvel
        if (isset($forumData['universe'])) {
            $forum->setUniverse($this->getReference($forumData['universe']));
        }
        
        if ($forumData['parent'] !== null) {
            $forum->setParent($this->getReference($forumData['parent']));
        }
        
        $manager->persist($forum);
        
        return $forum;
    }
    
    public static function getGroups(): array
    {
        return ['append-fixtures']; // Groupe pour permettre le chargement en append
    }
}

