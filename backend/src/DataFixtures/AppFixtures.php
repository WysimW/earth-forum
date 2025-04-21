<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class AppFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // Cette classe est vide car nous utilisons des fixtures spécifiques
        // pour chaque entité (UserFixtures, ForumFixtures, etc.)
        
        $manager->flush();
    }
    
    public static function getGroups(): array
    {
        return ['main-fixtures'];
    }
}
