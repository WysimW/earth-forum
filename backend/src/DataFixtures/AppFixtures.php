<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Cette classe est vide car nous utilisons des fixtures spécifiques
        // pour chaque entité (UserFixtures, ForumFixtures, etc.)
        
        $manager->flush();
    }
}
