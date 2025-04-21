<?php

namespace App\DataFixtures;

use App\Entity\Character;
use App\Entity\Univers;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class CharacterFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {

        // Personnages DC Comics classiques pour les utilisateurs
        $characters = [
            [
                'name' => 'Superman',
                'actualPseudo' => 'Clark Kent',
                'avatar' => 'https://via.placeholder.com/300x300?text=Superman',
                'type' => 'officiel',
                'biography' => "Né sur Krypton sous le nom de Kal-El, Superman a été envoyé sur Terre par ses parents avant la destruction de sa planète. Élevé par les Kent à Smallville sous le nom de Clark Kent, il utilise ses pouvoirs surhumains pour protéger l'humanité.",
                'status' => 'validated',
                'abilities' => 'Vol, super-force, invulnérabilité, super-vitesse, vision thermique, souffle glacial',
                'universe' => 'universe_dc',
                'user' => 'user_clarkk',
                'role' => 'role_hero'
            ],
            [
                'name' => 'Batman',
                'actualPseudo' => 'Bruce Wayne',
                'avatar' => 'https://via.placeholder.com/300x300?text=Batman',
                'type' => 'officiel',
                'biography' => "Après avoir assisté au meurtre de ses parents quand il était enfant, Bruce Wayne a juré de venger leur mort en combattant le crime à Gotham City. Sans super-pouvoirs, il utilise son intellect, ses compétences en arts martiaux et sa fortune pour devenir Batman.",
                'status' => 'validated',
                'abilities' => 'Génie tactique, maître en arts martiaux, équipement high-tech, détective hors pair',
                'universe' => 'universe_dc',
                'user' => 'user_brucew',
                'role' => 'role_hero'
            ],
            [
                'name' => 'Wonder Woman',
                'actualPseudo' => 'Diana Prince',
                'avatar' => 'https://via.placeholder.com/300x300?text=Wonder+Woman',
                'type' => 'officiel',
                'biography' => "Princesse des Amazones de Themyscira, Diana a quitté son île natale pour aider l'humanité dans son combat contre les forces du mal. Dotée de pouvoirs divins et armée de son lasso de vérité, elle est une guerrière redoutable.",
                'status' => 'validated',
                'abilities' => 'Super-force, agilité surhumaine, vol, réflexes améliorés, lasso de vérité, bracelets indestructibles',
                'universe' => 'universe_dc',
                'user' => 'user_dianap',
                'role' => 'role_hero'
            ],
            [
                'name' => 'The Flash',
                'actualPseudo' => 'Barry Allen',
                'avatar' => 'https://via.placeholder.com/300x300?text=Flash',
                'type' => 'officiel',
                'biography' => "Frappé par la foudre et aspergé de produits chimiques, Barry Allen est devenu l'homme le plus rapide du monde. En tant que Flash, il utilise sa super-vitesse pour protéger Central City et faire partie de la Justice League.",
                'status' => 'validated',
                'abilities' => 'Super-vitesse, guérison accélérée, perception du temps ralentie, voyage temporel',
                'universe' => 'universe_dc',
                'user' => 'user_barrya',
                'role' => 'role_hero'
            ],
            [
                'name' => 'Green Lantern',
                'actualPseudo' => 'Hal Jordan',
                'avatar' => 'https://via.placeholder.com/300x300?text=Green+Lantern',
                'type' => 'officiel',
                'biography' => "Ancien pilote d'essai, Hal Jordan a été choisi par l'anneau de pouvoir pour devenir un Green Lantern, membre du Corps des Green Lanterns qui protège l'univers. Sa volonté inébranlable lui permet de créer des constructions d'énergie verte.",
                'status' => 'validated',
                'abilities' => "Anneau de pouvoir, constructions d'énergie, vol, traduction universelle",
                'universe' => 'universe_dc',
                'user' => 'user_halj',
                'role' => 'role_hero'
            ],
            [
                'name' => 'Aquaman',
                'actualPseudo' => 'Arthur Curry',
                'avatar' => 'https://via.placeholder.com/300x300?text=Aquaman',
                'type' => 'officiel',
                'biography' => "Mi-humain, mi-Atlante, Arthur Curry est le roi de l'Atlantide et un protecteur des océans. Capable de respirer sous l'eau et de communiquer avec la vie marine, il est un membre puissant de la Justice League.",
                'status' => 'validated',
                'abilities' => "Respiration aquatique, super-force, résistance, communication avec la vie marine, contrôle des eaux",
                'universe' => 'universe_dc',
                'user' => 'user_arthurc',
                'role' => 'role_hero'
            ],
            [
                'name' => 'The Joker',
                'actualPseudo' => 'Inconnu',
                'avatar' => 'https://via.placeholder.com/300x300?text=Joker',
                'type' => 'officiel',
                'biography' => "L'ennemi juré de Batman, le Joker est un criminel psychotique dont les origines restent mystérieuses. Son objectif semble être de répandre le chaos et de pousser Batman à ses limites.",
                'status' => 'validated',
                'abilities' => 'Génie criminel, manipulation psychologique, gaz hilarant toxique, immunité à certains poisons',
                'universe' => 'universe_dc',
                'user' => 'user_jokermad',
                'role' => 'role_villain'
            ],
            [
                'name' => 'Harley Quinn',
                'actualPseudo' => 'Dr. Harleen Quinzel',
                'avatar' => 'https://via.placeholder.com/300x300?text=Harley+Quinn',
                'type' => 'officiel',
                'biography' => "Ancienne psychiatre à Arkham Asylum, le Dr. Harleen Quinzel est tombée amoureuse du Joker et est devenue sa complice sous le nom de Harley Quinn. Depuis, elle oscille entre criminalité et rédemption.",
                'status' => 'validated',
                'abilities' => 'Agilité exceptionnelle, compétences en combat, résistance aux toxines, formation en psychiatrie',
                'universe' => 'universe_dc',
                'user' => 'user_harleyq',
                'role' => 'role_antihero'
            ],
            [
                'name' => 'Lex Luthor',
                'actualPseudo' => 'Alexander Luthor',
                'avatar' => 'https://via.placeholder.com/300x300?text=Lex+Luthor',
                'type' => 'officiel',
                'biography' => "PDG de LexCorp et ennemi juré de Superman, Lex Luthor est un génie dont l'intelligence n'a d'égale que son ambition. Jaloux des pouvoirs de Superman, il cherche constamment à le détruire et à prouver la supériorité de l'humanité.",
                'status' => 'validated',
                'abilities' => 'Intelligence de niveau génie, fortune colossale, influence politique, armure de combat',
                'universe' => 'universe_dc',
                'user' => 'user_clarkk', // Utilisateur a créé deux personnages différents
                'role' => 'role_villain'
            ],
            [
                'name' => 'Lois Lane',
                'actualPseudo' => 'Lois Lane',
                'avatar' => 'https://via.placeholder.com/300x300?text=Lois+Lane',
                'type' => 'officiel',
                'biography' => "Journaliste intrépide au Daily Planet, Lois Lane est connue pour ses reportages d'investigation. Elle travaille aux côtés de Clark Kent et est l'un des rares personnages à connaître sa double identité en tant que Superman.",
                'status' => 'validated',
                'abilities' => "Journalisme d'investigation, courage, détermination",
                'universe' => 'universe_dc',
                'user' => 'user_dianap', // Diana a aussi un personnage civil
                'role' => 'role_civilian'
            ]
        ];

        $slugger = new AsciiSlugger();
        foreach ($characters as $characterData) {
            $character = new Character();
            $character->setName($characterData['name']);
            $character->setActualPseudo($characterData['actualPseudo']);
            $character->setAvatar($characterData['avatar']);
            $character->setBiography($characterData['biography']);
            $character->setStatus($characterData['status']);
            $character->setAbilities($characterData['abilities']);
            $character->setUniverse($this->getReference($characterData['universe']));
            $character->setUser($this->getReference($characterData['user']));
            
            // Génération explicite du slug
            $slug = $slugger->slug(strtolower($characterData['name']))->toString();
            $character->setSlug($slug);
            
            $character->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 180))));
            $character->setValidatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 150))));

            $manager->persist($character);
            
            // Référence
            $this->addReference('character_' . strtolower(str_replace([' ', '.'], '_', $characterData['name'])), $character);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
    }
    
    public static function getGroups(): array
    {
        return ['main-fixtures'];
    }
} 