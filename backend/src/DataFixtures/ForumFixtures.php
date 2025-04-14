<?php

namespace App\DataFixtures;

use App\Entity\Forum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ForumFixtures extends Fixture implements DependentFixtureInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $forums = [
            // Forums de la catégorie Terre
            [
                'name' => 'Metropolis',
                'description' => 'La ville de Superman, lumineuse et futuriste',
                'banner' => 'https://via.placeholder.com/800x200?text=Metropolis',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=Metro',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 1,
                'parent' => null
            ],
            // Sous-forums de Metropolis
            [
                'name' => 'Daily Planet',
                'description' => 'Le célèbre journal de Metropolis où travaille Clark Kent',
                'banner' => 'https://via.placeholder.com/800x200?text=Daily+Planet',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=DP',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 1,
                'parent' => 'forum_metropolis'
            ],
            [
                'name' => 'LexCorp',
                'description' => 'Le siège de l\'entreprise de Lex Luthor',
                'banner' => 'https://via.placeholder.com/800x200?text=LexCorp',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=LC',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 2,
                'parent' => 'forum_metropolis'
            ],
            [
                'name' => 'Rues de Metropolis',
                'description' => 'Les rues et quartiers de la ville brillante',
                'banner' => 'https://via.placeholder.com/800x200?text=Metropolis+Streets',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=MS',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 3,
                'parent' => 'forum_metropolis'
            ],
            // Gotham City
            [
                'name' => 'Gotham City',
                'description' => 'La ville sombre de Batman, rongée par le crime',
                'banner' => 'https://via.placeholder.com/800x200?text=Gotham+City',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=GC',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 2,
                'parent' => null
            ],
            // Sous-forums de Gotham
            [
                'name' => 'GCPD',
                'description' => 'Le département de police de Gotham City',
                'banner' => 'https://via.placeholder.com/800x200?text=GCPD',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=GCPD',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 1,
                'parent' => 'forum_gotham_city'
            ],
            [
                'name' => 'Arkham Asylum',
                'description' => 'L\'asile psychiatrique où sont enfermés les criminels les plus dangereux',
                'banner' => 'https://via.placeholder.com/800x200?text=Arkham+Asylum',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=AA',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 2,
                'parent' => 'forum_gotham_city'
            ],
            [
                'name' => 'Wayne Enterprises',
                'description' => 'Le siège de l\'entreprise de Bruce Wayne',
                'banner' => 'https://via.placeholder.com/800x200?text=Wayne+Enterprises',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=WE',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 3,
                'parent' => 'forum_gotham_city'
            ],
            [
                'name' => 'Batcave',
                'description' => 'Le repaire secret de Batman sous le manoir Wayne',
                'banner' => 'https://via.placeholder.com/800x200?text=Batcave',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=BC',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 4,
                'parent' => 'forum_gotham_city'
            ],
            // Organisations de héros
            [
                'name' => 'Justice League',
                'description' => 'Organisation des plus grands héros de la Terre',
                'banner' => 'https://via.placeholder.com/800x200?text=Justice+League',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=JL',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 3,
                'parent' => null
            ],
            // Sous-forums Justice League
            [
                'name' => 'Tour de garde',
                'description' => 'La base orbitale de la Justice League',
                'banner' => 'https://via.placeholder.com/800x200?text=Watchtower',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=WT',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 1,
                'parent' => 'forum_justice_league'
            ],
            [
                'name' => 'Hall of Justice',
                'description' => 'Le quartier général terrestre de la Justice League',
                'banner' => 'https://via.placeholder.com/800x200?text=Hall+of+Justice',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=HJ',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 2,
                'parent' => 'forum_justice_league'
            ],
            // Organisations de vilains
            [
                'name' => 'Super-Vilains',
                'description' => 'Les organisations criminelles et leurs repaires',
                'banner' => 'https://via.placeholder.com/800x200?text=Supervillains',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=SV',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 4,
                'parent' => null
            ],
            // Sous-forums Super-Vilains
            [
                'name' => 'Legion of Doom',
                'description' => 'Le groupe de super-vilains opposé à la Justice League',
                'banner' => 'https://via.placeholder.com/800x200?text=Legion+of+Doom',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=LoD',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 1,
                'parent' => 'forum_super-vilains'
            ],
            [
                'name' => 'Injustice League',
                'description' => 'Une autre organisation de super-vilains',
                'banner' => 'https://via.placeholder.com/800x200?text=Injustice+League',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=IL',
                'category' => 'category_terre',
                'isRoleplay' => true,
                'position' => 2,
                'parent' => 'forum_super-vilains'
            ],
            // Forums de la catégorie Univers
            [
                'name' => 'Themyscira',
                'description' => 'L\'île des Amazones, patrie de Wonder Woman',
                'banner' => 'https://via.placeholder.com/800x200?text=Themyscira',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=TM',
                'category' => 'category_univers',
                'isRoleplay' => true,
                'position' => 1,
                'parent' => null
            ],
            [
                'name' => 'Atlantis',
                'description' => 'Le royaume sous-marin d\'Aquaman',
                'banner' => 'https://via.placeholder.com/800x200?text=Atlantis',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=ATL',
                'category' => 'category_univers',
                'isRoleplay' => true,
                'position' => 2,
                'parent' => null
            ],
            // Forums HRP - Informations
            [
                'name' => 'Règlement',
                'description' => 'Règles du forum et informations importantes',
                'banner' => 'https://via.placeholder.com/800x200?text=Rules',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=RULES',
                'category' => 'category_informations',
                'isRoleplay' => false,
                'position' => 1,
                'parent' => null
            ],
            [
                'name' => 'Actualités DC Comics',
                'description' => 'Discussions sur les comics, films et séries DC',
                'banner' => 'https://via.placeholder.com/800x200?text=DC+News',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=DCN',
                'category' => 'category_informations',
                'isRoleplay' => false,
                'position' => 2,
                'parent' => null
            ],
            [
                'name' => 'Annonces',
                'description' => 'Annonces officielles concernant le forum',
                'banner' => 'https://via.placeholder.com/800x200?text=Annonces',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=ANN',
                'category' => 'category_informations',
                'isRoleplay' => false,
                'position' => 3,
                'parent' => null
            ],
            // Forums HRP - Présentations
            [
                'name' => 'Présentations membres',
                'description' => 'Présentez-vous à la communauté',
                'banner' => 'https://via.placeholder.com/800x200?text=Introductions',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=INT',
                'category' => 'category_présentations',
                'isRoleplay' => false,
                'position' => 1,
                'parent' => null
            ],
            [
                'name' => 'Fiches personnages',
                'description' => 'Présentations des personnages de roleplay',
                'banner' => 'https://via.placeholder.com/800x200?text=Characters',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=CHAR',
                'category' => 'category_présentations',
                'isRoleplay' => false,
                'position' => 2,
                'parent' => null
            ],
            // Forums HRP - Jeux
            [
                'name' => 'Flood',
                'description' => 'Discussions libres entre membres',
                'banner' => 'https://via.placeholder.com/800x200?text=Flood',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=FL',
                'category' => 'category_jeux',
                'isRoleplay' => false,
                'position' => 1,
                'parent' => null
            ],
            [
                'name' => 'Jeux forumiques',
                'description' => 'Jeux textuels entre membres',
                'banner' => 'https://via.placeholder.com/800x200?text=Games',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=GAMES',
                'category' => 'category_jeux',
                'isRoleplay' => false,
                'position' => 2,
                'parent' => null
            ],
            [
                'name' => 'Guide du débutant',
                'description' => 'Tout ce qu\'il faut savoir pour bien débuter sur le forum',
                'banner' => 'https://via.placeholder.com/800x200?text=Beginner+Guide',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=BG',
                'category' => 'category_informations',
                'isRoleplay' => false,
                'position' => 4,
                'parent' => null
            ],
            [
                'name' => 'Support technique',
                'description' => 'Besoin d\'aide avec le forum? C\'est par ici',
                'banner' => 'https://via.placeholder.com/800x200?text=Tech+Support',
                'heroLogo' => 'https://via.placeholder.com/100x100?text=TS',
                'category' => 'category_informations',
                'isRoleplay' => false,
                'position' => 5,
                'parent' => null
            ],
        ];

        // Première passe pour créer tous les forums principaux
        foreach ($forums as $key => $forumData) {
            if ($forumData['parent'] === null) {
                $forum = $this->createForum($forumData, $manager);
                
                // Générer la référence pour les forums parents
                $refName = 'forum_' . strtolower(str_replace([' ', '&'], '_', $forumData['name']));
                $this->addReference($refName, $forum);
            }
        }
        
        // Deuxième passe pour les sous-forums qui dépendent des forums parents
        foreach ($forums as $forumData) {
            if ($forumData['parent'] !== null) {
                $subforum = $this->createForum($forumData, $manager);
                
                // Créer une référence unique pour chaque sous-forum en incluant le parent
                $refName = 'subforum_' . strtolower(str_replace([' ', '&'], '_', $forumData['name']));
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
        $forum->setPosition($forumData['position']);
        $forum->setSlug($this->slugger->slug($forumData['name'])->lower());
        
        if ($forumData['parent'] !== null) {
            $forum->setParent($this->getReference($forumData['parent']));
        }
        
        $manager->persist($forum);
        
        // Ne pas créer de référence ici, car cela sera fait dans les méthodes appelantes
        
        return $forum;
    }

    public function getDependencies()
    {
        return [
            ForumCategoryFixtures::class,
        ];
    }
} 
