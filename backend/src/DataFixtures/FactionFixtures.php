<?php

namespace App\DataFixtures;

use App\Entity\Faction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class FactionFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $factions = [
            [
                'name' => 'Justice League',
                'description' => 'Organisation des plus grands héros de la Terre, fondée pour protéger le monde contre les menaces extraordinaires. La Justice League intervient lors des crises majeures nécessitant une réponse coordonnée de plusieurs super-héros.',
                'universe' => 'universe_dc',
                'alignment' => 'Héroïque',
                'scope' => 'Mondial',
                'objectives' => 'Protéger la Terre et ses habitants contre toute menace, qu\'elle soit terrestre, extraterrestre ou interdimensionnelle.',
                'headquarters' => 'location_watchtower',
                'founder' => 'user_clarkk',
                'characters' => [
                    'character_superman',
                    'character_batman',
                    'character_wonder_woman', 
                    'character_the_flash',
                    'character_green_lantern',
                    'character_aquaman'
                ],
                'logo' => 'https://via.placeholder.com/500x500?text=Justice+League',
                'icon' => 'https://via.placeholder.com/100x100?text=JL',
                'status' => Faction::STATUS_OPEN
            ],
            [
                'name' => 'Legion of Doom',
                'description' => 'Coalition de super-vilains unis par leur haine commune des héros et leur désir de domination mondiale. Dirigée initialement par Lex Luthor, cette organisation regroupe les esprits criminels les plus dangereux.',
                'universe' => 'universe_dc',
                'alignment' => 'Maléfique',
                'scope' => 'Mondial',
                'objectives' => 'Neutraliser la Justice League et établir une domination mondiale sous la gouvernance des super-vilains.',
                'headquarters' => 'location_lexcorp',
                'founder' => 'user_clarkk', // Lex Luthor est joué par le même utilisateur que Superman
                'characters' => [
                    'character_lex_luthor',
                    'character_the_joker'
                ],
                'logo' => 'https://via.placeholder.com/500x500?text=Legion+of+Doom',
                'icon' => 'https://via.placeholder.com/100x100?text=LoD',
                'status' => Faction::STATUS_OPEN
            ],
            [
                'name' => 'Daily Planet',
                'description' => 'Journal emblématique de Metropolis, couvrant l\'actualité locale et internationale. Malgré sa taille modeste, le Daily Planet a souvent été au cœur des événements majeurs, notamment grâce à ses reporters comme Clark Kent et Lois Lane.',
                'universe' => 'universe_dc',
                'alignment' => 'Neutre',
                'scope' => 'Local',
                'objectives' => 'Informer les citoyens, enquêter sur les affaires de corruption et documenter les activités des super-héros et super-vilains.',
                'headquarters' => 'location_daily_planet',
                'founder' => 'user_dianap', // Lois Lane
                'characters' => [
                    'character_lois_lane',
                    'character_superman' // Dans son identité de Clark Kent
                ],
                'logo' => 'https://via.placeholder.com/500x500?text=Daily+Planet',
                'icon' => 'https://via.placeholder.com/100x100?text=DP',
                'status' => Faction::STATUS_OPEN
            ],
            [
                'name' => 'Bat-Family',
                'description' => 'Groupe d\'alliés et de protégés de Batman opérant principalement à Gotham City. Bien que moins formelle que la Justice League, la Bat-Family partage des ressources et s\'entraide pour lutter contre le crime.',
                'universe' => 'universe_dc',
                'alignment' => 'Héroïque',
                'scope' => 'Local',
                'objectives' => 'Protéger Gotham City et ses habitants contre le crime organisé et les super-vilains locaux.',
                'headquarters' => 'location_batcave',
                'founder' => 'user_brucew',
                'characters' => [
                    'character_batman'
                ],
                'logo' => 'https://via.placeholder.com/500x500?text=Bat-Family',
                'icon' => 'https://via.placeholder.com/100x100?text=BF',
                'status' => Faction::STATUS_OPEN
            ],
            [
                'name' => 'Amazones de Themyscira',
                'description' => 'Société guerrière exclusivement féminine habitant l\'île de Themyscira. Créées par les dieux de l\'Olympe, les Amazones sont connues pour leurs compétences martiales, leur longévité et leur culture avancée.',
                'universe' => 'universe_dc',
                'alignment' => 'Héroïque',
                'scope' => 'National',
                'objectives' => 'Préserver la paix et la prospérité de Themyscira, tout en envoyant des émissaires comme Wonder Woman pour défendre les valeurs amazones dans le monde extérieur.',
                'headquarters' => 'location_themyscira',
                'founder' => 'user_dianap',
                'characters' => [
                    'character_wonder_woman'
                ],
                'logo' => 'https://via.placeholder.com/500x500?text=Amazones',
                'icon' => 'https://via.placeholder.com/100x100?text=AMZ',
                'status' => Faction::STATUS_OPEN
            ]
        ];

        foreach ($factions as $factionData) {
            $faction = new Faction();
            $faction->setName($factionData['name']);
            $faction->setDescription($factionData['description']);
            $faction->setUniverse($this->getReference($factionData['universe']));
            $faction->setAlignment($factionData['alignment']);
            $faction->setScope($factionData['scope']);
            $faction->setObjectives($factionData['objectives']);
            $faction->setHeadquarters($this->getReference($factionData['headquarters']));
            $faction->setFounder($this->getReference($factionData['founder']));
            $faction->setLogo($factionData['logo']);
            $faction->setIcon($factionData['icon']);
            $faction->setStatus($factionData['status']);
            
            // Le slug sera généré automatiquement par les méthodes du cycle de vie

            // Ajout des personnages à la faction
            foreach ($factionData['characters'] as $characterRef) {
                $character = $this->getReference($characterRef);
                $faction->addCharacter($character);
            }

            $manager->persist($faction);
            
            // Ajouter une référence pour une utilisation potentielle dans d'autres fixtures
            $refName = 'faction_' . strtolower(str_replace([' ', '-', "'", '.', '&'], '_', $factionData['name']));
            $this->addReference($refName, $faction);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            CharacterFixtures::class,
            LocationFixtures::class,
            UniverseFixtures::class,
            UserFixtures::class
        ];
    }
    
    public static function getGroups(): array
    {
        return ['main-fixtures'];
    }
} 