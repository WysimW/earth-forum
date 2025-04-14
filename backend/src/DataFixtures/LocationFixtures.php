<?php

namespace App\DataFixtures;

use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

class LocationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();
        
        $locations = [
            [
                'name' => 'Metropolis',
                'description' => 'Ville moderne et futuriste, siège du Daily Planet et résidence de Superman. Connue pour ses gratte-ciels étincelants et sa technologie de pointe, Metropolis représente l\'espoir et le progrès dans l\'univers DC.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2020/06-JUN/MetropolisFacts_5ee8f7759d7bb9.96922212.jpg',
                'city' => 'Metropolis',
                'country' => 'États-Unis',
                'universe' => 'dc',
                'isPublic' => true,
            ],
            [
                'name' => 'Gotham City',
                'description' => 'Cité sombre et gothique, repaire de Batman et de nombreux super-vilains. Avec son architecture néo-gothique et ses ruelles ténébreuses, Gotham incarne la lutte contre la criminalité et la corruption.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2020/06-JUN/GothamFacts_5ee8f69b528c30.00512073.jpg',
                'city' => 'Gotham City',
                'country' => 'États-Unis',
                'universe' => 'dc',
                'isPublic' => true,
            ],
            [
                'name' => 'Daily Planet',
                'description' => 'Journal emblématique de Metropolis où travaillent Clark Kent et Lois Lane. Reconnaissable à son imposant globe terrestre en or au sommet de son immeuble, le Daily Planet est le symbole du journalisme intègre.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2020/06-JUN/MetropolisFacts_5ee8f7759d7bb9.96922212.jpg',
                'city' => 'Metropolis',
                'country' => 'États-Unis',
                'universe' => 'dc',
                'parent' => 'Metropolis',
                'isPublic' => true,
            ],
            [
                'name' => 'Batcave',
                'description' => 'Quartier général secret de Batman situé sous le Manoir Wayne. Centre technologique ultra-sophistiqué comprenant le Bat-ordinateur, les différents véhicules de Batman et sa collection de trophées.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2015/08-AUG/BatcaveTA_55cb97f6acaba8.11031147.jpg',
                'city' => 'Gotham City',
                'country' => 'États-Unis',
                'universe' => 'dc',
                'parent' => 'Gotham City',
                'isPublic' => true,
            ],
            [
                'name' => 'Themyscira',
                'description' => 'Île paradisiaque cachée du monde des hommes, patrie des Amazones et lieu de naissance de Wonder Woman. Cette société matriarcale avancée combine technologie et traditions ancestrales.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2020/06-JUN/ThemysciraFacts_5ee8fb1ba97f95.27436949.jpg',
                'city' => 'Themyscira',
                'country' => null,
                'universe' => 'dc',
                'isPublic' => true,
            ],
            [
                'name' => 'Arkham Asylum',
                'description' => 'Institution psychiatrique de haute sécurité pour criminels dangereux à Gotham. Cet asile sinistre a hébergé de nombreux ennemis de Batman comme le Joker, Double-Face et l\'Épouvantail.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2017/10-OCT/ArkhamAsylum_59d2ab2b7e2c45.67892549.jpg',
                'city' => 'Gotham City',
                'country' => 'États-Unis',
                'universe' => 'dc',
                'parent' => 'Gotham City',
                'isPublic' => true,
            ],
            [
                'name' => 'Hall of Justice',
                'description' => 'Quartier général de la Justice League situé à Washington DC. Ce bâtiment imposant symbolise l\'alliance des plus grands héros de la Terre pour protéger l\'humanité.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2018/08-AUG/HOJ_5b6b73c9a94a30.36402489.jpg',
                'city' => 'Washington',
                'country' => 'États-Unis',
                'universe' => 'dc',
                'isPublic' => true,
            ],
            [
                'name' => 'Watchtower',
                'description' => 'Station spatiale orbitale servant de base secondaire à la Justice League. Offrant une vue imprenable sur la Terre, elle permet une intervention rapide partout dans le monde.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2017/02-FEB/Watchtower_58a20a8c465507.36982120.jpg',
                'city' => null,
                'country' => null,
                'universe' => 'dc',
                'isPublic' => true,
            ],
            [
                'name' => 'LexCorp',
                'description' => 'Multinationale dirigée par Lex Luthor à Metropolis. Cette entreprise technologique est à la pointe de l\'innovation, servant souvent de façade aux machinations de son propriétaire contre Superman.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2016/07-JUL/LexCorp_579fa04bb94ba8.69362471.jpg',
                'city' => 'Metropolis',
                'country' => 'États-Unis',
                'universe' => 'dc',
                'parent' => 'Metropolis',
                'isPublic' => true,
            ],
            [
                'name' => 'Central City',
                'description' => 'Ville natale de Barry Allen, The Flash. Cette métropole moderne est connue pour ses laboratoires S.T.A.R. et l\'accident qui a créé de nombreux méta-humains.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2014/10-OCT/CentralCity_5430e9c3c87f13.25693141.jpg',
                'city' => 'Central City',
                'country' => 'États-Unis',
                'universe' => 'dc',
                'isPublic' => true,
            ],
            [
                'name' => 'Atlantis',
                'description' => 'Royaume sous-marin avancé dirigé par Aquaman. Cette civilisation ancienne dispose d\'une technologie supérieure et d\'une connexion mystique avec les océans.',
                'image' => 'https://www.dccomics.com/sites/default/files/imce/2018/12-DEC/Atlantis_5c1bf2c2c64d55.28456418.jpg',
                'city' => 'Atlantis',
                'country' => null,
                'universe' => 'dc',
                'isPublic' => true,
            ],
        ];

        // Stockage des références pour les lieux parents
        $locationReferences = [];

        // Premier passage pour créer tous les lieux
        foreach ($locations as $locationData) {
            $location = new Location();
            $location->setName($locationData['name']);
            $location->setDescription($locationData['description']);
            $location->setImage($locationData['image']);
            $location->setCity($locationData['city']);
            $location->setCountry($locationData['country']);
            $location->setIsPublic($locationData['isPublic']);
            
            // Génération du slug à partir du nom
            $slug = strtolower($slugger->slug($locationData['name']));
            $location->setSlug($slug);
            
            if (isset($locationData['universe'])) {
                $universe = $this->getReference('universe_' . $locationData['universe']);
                $location->setUniverse($universe);
            }
            
            $manager->persist($location);
            
            // Stocker une référence au lieu avec le format location_xxx
            // Utilisons le même format de transformation pour éviter les incohérences
            $locationName = strtolower($locationData['name']);
            // Remplacer tous les espaces, tirets et autres caractères spéciaux par des underscores
            $refName = 'location_' . str_replace([' ', '-', "'", '.', '&'], '_', $locationName);
            $this->addReference($refName, $location);
            
            // Debug pour voir les clés de référence créées
            echo "Created location reference: " . $refName . "\n";
            
            // Stocker une référence au lieu (pour les relations parent-enfant)
            $locationReferences[$locationData['name']] = $location;
        }

        // Flush pour s'assurer que tous les lieux sont créés
        $manager->flush();

        // Deuxième passage pour établir les relations parent-enfant
        foreach ($locations as $locationData) {
            if (isset($locationData['parent'])) {
                $location = $locationReferences[$locationData['name']];
                $parentLocation = $locationReferences[$locationData['parent']];
                $location->setParent($parentLocation);
                $manager->persist($location);
            }
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UniverseFixtures::class,
        ];
    }
} 