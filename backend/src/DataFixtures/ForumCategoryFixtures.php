<?php

namespace App\DataFixtures;

use App\Entity\ForumCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class ForumCategoryFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();
        
        $categories = [
            // Catégories RP
            [
                'name' => 'Terre',
                'description' => 'Forums associés à la Terre principale dans l\'univers DC',
                'type' => 'category_type_roleplay',
                'homeOrder' => 1
            ],
            [
                'name' => 'Univers',
                'description' => 'Forums associés aux différents univers et planètes de DC Comics',
                'type' => 'category_type_roleplay',
                'homeOrder' => 2
            ],
            [
                'name' => 'Elseworld',
                'description' => 'Forums dédiés aux réalités alternatives de l\'univers DC',
                'type' => 'category_type_roleplay',
                'homeOrder' => 3
            ],
            // Catégories HRP
            [
                'name' => 'Informations',
                'description' => 'Forums contenant les informations essentielles du site',
                'type' => 'category_type_hors_roleplay',
                'homeOrder' => 4
            ],
            [
                'name' => 'Présentations',
                'description' => 'Forums dédiés aux présentations des membres et personnages',
                'type' => 'category_type_hors_roleplay',
                'homeOrder' => 5
            ],
            [
                'name' => 'Jeux',
                'description' => 'Forums dédiés aux jeux et activités hors roleplay',
                'type' => 'category_type_hors_roleplay',
                'homeOrder' => 6
            ]
        ];

        foreach ($categories as $categoryData) {
            $category = new ForumCategory();
            $category->setName($categoryData['name']);
            $category->setDescription($categoryData['description']);
            $category->setType($this->getReference($categoryData['type']));
            $category->setHomeOrder($categoryData['homeOrder']);
            
            // Génération du slug
            $slug = strtolower($slugger->slug($categoryData['name']));
            $category->setSlug($slug);

            $manager->persist($category);
            
            // Référence pour une utilisation ultérieure
            $this->addReference('category_' . strtolower(str_replace(' ', '_', $categoryData['name'])), $category);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            CategoriesTypeFixtures::class,
        ];
    }
    
    public static function getGroups(): array
    {
        return ['main-fixtures'];
    }
} 