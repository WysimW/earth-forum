<?php

namespace App\DataFixtures;

use App\Entity\Univers;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

class UniverseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {   
        $slugger = new AsciiSlugger();
        $universes = [
            [
                'name' => 'DC Comics',
                'description' => 'L\'univers des super-héros DC comme Superman, Batman, Wonder Woman et bien d\'autres. Cet univers comprend des lieux emblématiques comme Metropolis, Gotham City et Themyscira.',
                'slug' => 'dc',
            ],
            [
                'name' => 'Marvel',
                'description' => 'L\'univers des super-héros Marvel comme Spider-Man, Iron Man, Captain America et les X-Men. Cet univers inclut des lieux comme New York, Wakanda et Asgard.',
                'slug' => 'marvel',
            ],
            [
                'name' => 'Star Wars',
                'description' => 'L\'univers de Star Wars créé par George Lucas, avec ses planètes diverses, ses Jedi, ses Sith et l\'Empire Galactique.',
                'slug' => 'star-wars',
            ],
            [
                'name' => 'Monde RPG Original',
                'description' => 'Un univers de fantasy original pour les membres souhaitant créer leurs propres histoires et personnages dans un cadre médiéval fantastique.',
                'slug' => 'fantasy-rpg',
            ],
        ];

        // Création des univers
        foreach ($universes as $universeData) {
            $universe = new Univers();
            $universe->setName($universeData['name']);
            $universe->setDescription($universeData['description']);
            
            // Le slug est déjà défini dans les données
            $universe->setSlug($universeData['slug']);
            
            $universe->setCreatedAt(new \DateTimeImmutable());
            
            $manager->persist($universe);
            
            // Ajout d'une référence pour chaque univers
            $this->addReference('universe_' . $universeData['slug'], $universe);
        }

        $manager->flush();
    }
} 