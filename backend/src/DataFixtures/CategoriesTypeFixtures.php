<?php

namespace App\DataFixtures;

use App\Entity\CategoriesType;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CategoriesTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {   
        $slugger = new AsciiSlugger();

        $types = [
            [
                'name' => 'Roleplay',
                'description' => 'Forums dédiés au roleplay dans l\'univers DC Comics',
                'color' => '#0476F2'
            ],
            [
                'name' => 'Hors-Roleplay',
                'description' => 'Forums dédiés aux discussions hors personnage',
                'color' => '#015f40'
            ]
        ];

        foreach ($types as $key => $typeData) {
            $type = new CategoriesType();
            $type->setName($typeData['name']);

            $slug = strtolower($slugger->slug($typeData['name']));
            $type->setSlug($slug);

            $manager->persist($type);
            
            // Référence pour une utilisation ultérieure
            $this->addReference('category_type_' . strtolower(str_replace('-', '_', $typeData['name'])), $type);
        }

        $manager->flush();
    }
} 