<?php

namespace App\DataFixtures;

use App\Entity\Univers;
use App\Entity\Forum;
use App\Entity\ForumCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class StarWarsFixtures extends Fixture implements FixtureGroupInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer l'univers Star Wars
        $starWarsUniverse = new Univers();
        $starWarsUniverse->setName('Star Wars');
        $starWarsUniverse->setDescription('L\'univers épique de Star Wars, de la République à l\'Empire, en passant par la Résistance. Explorez les planètes, les vaisseaux et les batailles de cette galaxie lointaine, très lointaine.');
        $starWarsUniverse->setSlug('star-wars');
        $starWarsUniverse->setCreatedAt(new \DateTimeImmutable());
        
        $manager->persist($starWarsUniverse);
        $manager->flush();
        
        // Ajouter une référence pour l'univers
        $this->addReference('universe_star_wars', $starWarsUniverse);
        
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
            // Forums de la catégorie Univers - Planètes principales
            [
                'name' => 'Coruscant',
                'description' => 'La capitale de la République Galactique et de l\'Empire, planète-ville entièrement urbanisée',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Sous-forums de Coruscant
            [
                'name' => 'Sénat Galactique',
                'description' => 'Le siège du pouvoir politique de la galaxie',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_coruscant',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Temple Jedi',
                'description' => 'L\'ancien siège de l\'Ordre Jedi avant la Purge',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_coruscant',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Niveaux inférieurs',
                'description' => 'Les bas-fonds de Coruscant, repaire de criminels et de contrebandiers',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => 'forum_coruscant',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Tatooine',
                'description' => 'Planète désertique des Territoires de la Bordure Extérieure, foyer d\'Anakin et Luke Skywalker',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Sous-forums de Tatooine
            [
                'name' => 'Mos Eisley',
                'description' => 'La ville portuaire la plus célèbre de Tatooine, repaire de contrebandiers',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_tatooine',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Mos Espa',
                'description' => 'Ville natale d\'Anakin Skywalker, célèbre pour ses courses de pods',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_tatooine',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Désert de Jundland',
                'description' => 'Les terres sauvages de Tatooine, territoire des Tuskens',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => 'forum_tatooine',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Alderaan',
                'description' => 'Planète pacifique de la Bordure Intérieure, détruite par l\'Étoile de la Mort',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Naboo',
                'description' => 'Planète verdoyante de la Bordure Intérieure, patrie de Padmé Amidala',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 4,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Sous-forums de Naboo
            [
                'name' => 'Théed',
                'description' => 'La capitale de Naboo, ville aux architectures élégantes',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_naboo',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Otoh Gunga',
                'description' => 'La cité sous-marine des Gungans',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_naboo',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Hoth',
                'description' => 'Planète de glace où la Rébellion établit sa base Echo',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 5,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Dagobah',
                'description' => 'Planète marécageuse où Yoda s\'est exilé',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 6,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Endor',
                'description' => 'Planète forestière des Ewoks, lieu de la bataille finale contre l\'Empire',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 7,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Jakku',
                'description' => 'Planète désertique où Rey a grandi, champ de bataille de l\'Empire et de la Nouvelle République',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 8,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Kamino',
                'description' => 'Planète océanique où les clones de l\'armée de la République ont été créés',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 9,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Mustafar',
                'description' => 'Planète volcanique où Anakin Skywalker est devenu Dark Vador',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 10,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Organisations et factions
            [
                'name' => 'L\'Ordre Jedi',
                'description' => 'Les gardiens de la paix et de la justice dans la galaxie',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 11,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Sous-forums de l'Ordre Jedi
            [
                'name' => 'Conseil Jedi',
                'description' => 'Le conseil dirigeant de l\'Ordre Jedi',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_l_ordre_jedi',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Académie Jedi',
                'description' => 'L\'école où les jeunes sensibles à la Force sont formés',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_l_ordre_jedi',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Les Sith',
                'description' => 'Les utilisateurs du Côté Obscur de la Force',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 12,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Sous-forums des Sith
            [
                'name' => 'Règle des Deux',
                'description' => 'La doctrine Sith limitant leur nombre à un Maître et un Apprenti',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_les_sith',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'L\'Empire Galactique',
                'description' => 'Le régime autoritaire dirigé par l\'Empereur Palpatine',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 13,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Sous-forums de l'Empire
            [
                'name' => 'Étoile de la Mort',
                'description' => 'La station de combat impériale capable de détruire des planètes',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_l_empire_galactique',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Stormtroopers',
                'description' => 'Les soldats d\'élite de l\'Empire',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_l_empire_galactique',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'La Rébellion',
                'description' => 'L\'Alliance pour restaurer la République, opposée à l\'Empire',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 14,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Sous-forums de la Rébellion
            [
                'name' => 'Base Echo',
                'description' => 'La base secrète de la Rébellion sur Hoth',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_la_rebellion',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Escadron Rogue',
                'description' => 'L\'unité de chasseurs de la Rébellion',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_la_rebellion',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'La Résistance',
                'description' => 'L\'organisation militaire opposée au Premier Ordre',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 15,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Le Premier Ordre',
                'description' => 'L\'organisation militaire héritière de l\'Empire',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 16,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Vaisseaux et stations spatiales
            [
                'name' => 'Vaisseaux et Stations',
                'description' => 'Les vaisseaux spatiaux et stations de la galaxie',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 17,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
            // Sous-forums Vaisseaux
            [
                'name' => 'Faucon Millenium',
                'description' => 'Le célèbre vaisseau de contrebande de Han Solo',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 1,
                'parent' => 'forum_vaisseaux_et_stations',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Destroyers Stellaires',
                'description' => 'Les vaisseaux de guerre de l\'Empire',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 2,
                'parent' => 'forum_vaisseaux_et_stations',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'Chasseurs TIE',
                'description' => 'Les chasseurs spatiaux de l\'Empire',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 3,
                'parent' => 'forum_vaisseaux_et_stations',
                'universe' => 'universe_star_wars'
            ],
            [
                'name' => 'X-Wings',
                'description' => 'Les chasseurs de la Rébellion',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_univers',
                'isRoleplay' => true,
                'type' => 'roleplay',
                'position' => 4,
                'parent' => 'forum_vaisseaux_et_stations',
                'universe' => 'universe_star_wars'
            ],
            // Forums HRP - Informations
            [
                'name' => 'Actualités Star Wars',
                'description' => 'Discussions sur les films, séries, livres et comics Star Wars',
                'banner' => null,
                'heroLogo' => null,
                'category' => 'category_informations',
                'isRoleplay' => false,
                'type' => 'hrp',
                'position' => 1,
                'parent' => null,
                'universe' => 'universe_star_wars'
            ],
        ];

        // Première passe pour créer tous les forums principaux
        foreach ($forums as $key => $forumData) {
            if ($forumData['parent'] === null) {
                $forum = $this->createForum($forumData, $manager, $starWarsUniverse);
                
                // Générer la référence pour les forums parents en utilisant le slugger pour normaliser
                $normalizedName = $this->slugger->slug($forumData['name'])->lower()->toString();
                $refName = 'forum_' . str_replace('-', '_', $normalizedName);
                $this->addReference($refName, $forum);
            }
        }
        
        // Deuxième passe pour les sous-forums qui dépendent des forums parents
        foreach ($forums as $forumData) {
            if ($forumData['parent'] !== null) {
                $subforum = $this->createForum($forumData, $manager, $starWarsUniverse);
                
                // Créer une référence unique pour chaque sous-forum en incluant le parent
                $normalizedName = $this->slugger->slug($forumData['name'])->lower()->toString();
                $refName = 'subforum_' . str_replace('-', '_', $normalizedName);
                $this->addReference($refName, $subforum);
            }
        }

        $manager->flush();
    }

    private function createForum(array $forumData, ObjectManager $manager, Univers $universe): Forum
    {
        $forum = new Forum();
        $forum->setName($forumData['name']);
        $forum->setDescription($forumData['description']);
        $forum->setBanner($forumData['banner']); // null comme demandé
        $forum->setHeroLogo($forumData['heroLogo']); // null comme demandé
        $forum->setCategory($this->getReference($forumData['category']));
        $forum->setIsRoleplay($forumData['isRoleplay']);
        $forum->setType($forumData['type']);
        $forum->setPosition($forumData['position']);
        // Préfixer le slug avec "star-wars-" pour éviter les conflits avec d'autres univers
        $baseSlug = $this->slugger->slug($forumData['name'])->lower();
        $forum->setSlug('star-wars-' . $baseSlug);
        $forum->setUniverse($universe);
        
        if ($forumData['parent'] !== null) {
            $forum->setParent($this->getReference($forumData['parent']));
        }
        
        $manager->persist($forum);
        
        return $forum;
    }
    
    public static function getGroups(): array
    {
        return ['star-wars-fixtures'];
    }
}

