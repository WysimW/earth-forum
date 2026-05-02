<?php

namespace App\DataFixtures;

use App\Entity\Thread;
use App\Entity\Post;
use App\Entity\Forum;
use App\Entity\User;
use App\Entity\Character;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class GothamCityThreadsFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function load(ObjectManager $manager): void
    {
        // Trouver le forum Gotham City
        $gothamCityForum = $manager->getRepository(Forum::class)->findOneBy(['name' => 'Gotham City']);
        
        if (!$gothamCityForum) {
            throw new \Exception("Le forum Gotham City n'existe pas. Assurez-vous que ForumFixtures est chargé avant.");
        }

        // Récupérer tous les sous-forums de Gotham City
        $subForums = $manager->getRepository(Forum::class)->findBy(['parent' => $gothamCityForum]);
        $allForums = array_merge([$gothamCityForum], $subForums);

        // Récupérer tous les utilisateurs
        $users = $manager->getRepository(User::class)->findAll();
        if (empty($users)) {
            throw new \Exception("Aucun utilisateur trouvé. Assurez-vous que UserFixtures est chargé avant.");
        }

        // Récupérer tous les personnages
        $characters = $manager->getRepository(Character::class)->findAll();
        if (empty($characters)) {
            throw new \Exception("Aucun personnage trouvé. Assurez-vous que CharacterFixtures est chargé avant.");
        }

        // Titres de threads HRP variés
        $hrpTitles = [
            'Discussion générale sur Gotham',
            'Les dernières nouvelles de la ville',
            'Qui est le meilleur héros de Gotham ?',
            'Les méchants les plus dangereux',
            'Stratégies de patrouille',
            'Nouvelles technologies Wayne Enterprises',
            'Rapport d\'activité du GCPD',
            'Événements à venir',
            'Questions sur le lore',
            'Fan theories',
            'Art et fanart',
            'Cosplay et conventions',
            'Jeux vidéo Batman',
            'Comics récents',
            'Films et séries',
            'Musique de Gotham',
            'Architecture de la ville',
            'Histoire de Gotham',
            'Géographie de la ville',
            'Personnages secondaires',
            'Événements majeurs',
            'Timeline des événements',
            'Relations entre personnages',
            'Pouvoirs et capacités',
            'Équipements et gadgets',
            'Véhicules emblématiques',
            'Lieux emblématiques',
            'Organisations criminelles',
            'Alliances et factions',
            'Rivalités légendaires',
            'Mystères non résolus',
            'Théories sur les origines',
            'Évolutions des personnages',
            'Crossovers avec autres univers',
            'Adaptations et réinterprétations',
        ];

        // Titres de threads RP variés
        $rpTitles = [
            'Patrouille nocturne dans les rues',
            'Une nouvelle menace apparaît',
            'Rencontre inattendue',
            'Mission secrète au port',
            'Enquête sur un mystère',
            'Confrontation dans les toits',
            'Alliance temporaire',
            'Traque dans les égouts',
            'Infiltration dans un repaire',
            'Sauvetage en cours',
            'Combat épique',
            'Négociation délicate',
            'Découverte importante',
            'Course-poursuite',
            'Piège tendu',
            'Révélation surprenante',
            'Mission de sauvetage',
            'Enquête sur un meurtre',
            'Confrontation avec un ennemi',
            'Alliance stratégique',
            'Trahison inattendue',
            'Rédemption possible',
            'Nouvelle recrue',
            'Formation intensive',
            'Mission de reconnaissance',
            'Opération de sauvetage',
            'Confrontation finale',
            'Décision difficile',
            'Conséquences inattendues',
            'Nouveau départ',
        ];

        // Contenus de posts HRP variés
        $hrpContents = [
            'Je pense que Batman est vraiment le meilleur héros de Gotham.',
            'Qu\'en pensez-vous de cette nouvelle ?',
            'C\'est une excellente question !',
            'Je suis d\'accord avec toi sur ce point.',
            'Il y a beaucoup de choses intéressantes à discuter ici.',
            'J\'aimerais avoir votre avis sur ce sujet.',
            'C\'est un point de vue intéressant.',
            'Je ne suis pas tout à fait d\'accord, mais je comprends.',
            'Merci pour cette information !',
            'C\'est vraiment fascinant.',
            'J\'ai toujours pensé que...',
            'Quelle est votre opinion sur ce sujet ?',
            'Je trouve cela très intéressant.',
            'Il y a plusieurs façons de voir les choses.',
            'C\'est un débat passionnant.',
            'Je pense qu\'on devrait approfondir cette question.',
            'Excellente observation !',
            'Je partage ton point de vue.',
            'C\'est une perspective intéressante.',
            'Merci pour ce partage.',
        ];

        // Contenus de posts RP variés
        $rpContents = [
            '*Batman atterrit silencieusement sur le toit d\'un immeuble, scrutant les rues sombres en contrebas.*',
            '*Le vent souffle dans les cheveux alors que je cours sur les toits.*',
            '"Il faut que je trouve des indices", murmure-t-il en examinant la scène.',
            '*Un bruit suspect attire mon attention. Je me tourne lentement.*',
            '"Tu ne t\'en tireras pas comme ça", lance-t-il d\'une voix ferme.',
            '*Je me cache dans l\'ombre, observant la situation.*',
            '"Nous devons travailler ensemble sur ce coup", propose-t-il.',
            '*Les lumières de la ville scintillent au loin.*',
            '"C\'est plus compliqué que prévu", admet-elle en soupirant.',
            '*Je saute de toit en toit, poursuivant ma cible.*',
            '"Il y a quelque chose qui ne va pas ici", remarque-t-il.',
            '*Le combat éclate dans un échange de coups rapides.*',
            '"Je ne peux pas te laisser faire ça", déclare-t-il fermement.',
            '*Je me faufile dans l\'obscurité, invisible aux yeux de tous.*',
            '"Nous avons besoin d\'un plan", suggère-t-elle.',
            '*L\'adrénaline monte alors que je me prépare à agir.*',
            '"C\'est maintenant ou jamais", se dit-il intérieurement.',
            '*Les sirènes de police résonnent au loin.*',
            '"Je dois trouver une solution rapidement", pense-t-elle.',
            '*Le danger se rapproche, mais je reste calme.*',
        ];

        $threadCount = 0;
        $targetThreads = 100;
        $usedSlugs = []; // Pour éviter les doublons de slugs

        // Créer les threads
        while ($threadCount < $targetThreads) {
            // Choisir un forum aléatoire
            $forum = $allForums[array_rand($allForums)];
            
            // Déterminer le type (70% RP pour les forums RP, sinon 50/50)
            $isRoleplay = $forum->isRoleplay() || $forum->getType() === 'roleplay';
            $threadType = ($isRoleplay && rand(1, 100) <= 70) || (!$isRoleplay && rand(1, 100) <= 50) ? 'roleplay' : 'hrp';
            
            // Choisir un titre selon le type
            $baseTitle = $threadType === 'roleplay' 
                ? $rpTitles[array_rand($rpTitles)] 
                : $hrpTitles[array_rand($hrpTitles)];
            
            // Ajouter un numéro ou variante pour éviter les doublons
            $title = $baseTitle;
            $suffix = '';
            if (rand(1, 3) === 1) {
                $suffix = ' - Partie ' . rand(1, 5);
                $title .= $suffix;
            }
            
            // Générer un slug unique
            $baseSlug = $this->slugger->slug($baseTitle)->lower();
            $slug = $baseSlug;
            $slugCounter = 1;
            while (in_array($slug, $usedSlugs)) {
                $slug = $baseSlug . '-' . $slugCounter;
                $slugCounter++;
            }
            $usedSlugs[] = $slug;

            // Créer le thread
            $thread = new Thread();
            $thread->setTitle($title);
            $thread->setDescription(null);
            $thread->setForum($forum);
            $thread->setType($threadType);
            $thread->setStatus('open');
            $thread->setSticky(false);
            $thread->setElseworld(null); // Important pour éviter les erreurs
            
            // Date de création aléatoire (derniers 90 jours)
            $daysAgo = rand(0, 90);
            $hoursAgo = rand(0, 23);
            $thread->setCreatedAt(new \DateTimeImmutable("-$daysAgo days -$hoursAgo hours"));
            $thread->setUpdatedAt($thread->getCreatedAt());
            
            // Slug unique
            $thread->setSlug($slug);
            
            // Choisir un auteur aléatoire
            $author = $users[array_rand($users)];
            $thread->setAuthor($author);
            
            // Pour les threads RP, assigner un characterCreator
            if ($threadType === 'roleplay') {
                $rpCharacters = array_filter($characters, function($char) {
                    return $char->getStatus() === 'validated';
                });
                if (!empty($rpCharacters)) {
                    $characterCreator = $rpCharacters[array_rand($rpCharacters)];
                    $thread->setCharacterCreator($characterCreator);
                }
            }
            
            $manager->persist($thread);
            
            // Créer les posts pour ce thread (2 à 10 posts)
            $postCount = rand(2, 10);
            $threadCreatedAt = $thread->getCreatedAt();
            
            for ($i = 0; $i < $postCount; $i++) {
                $post = new Post();
                
                // Contenu selon le type
                if ($threadType === 'roleplay') {
                    $content = $rpContents[array_rand($rpContents)];
                    // Parfois ajouter du dialogue
                    if (rand(1, 3) === 1) {
                        $dialogues = [
                            '"Il faut agir maintenant !"',
                            '"Je ne suis pas d\'accord avec ça."',
                            '"Suivez-moi, je connais un chemin."',
                            '"Attention, il y a quelqu\'un !"',
                            '"Nous devons être prudents."',
                        ];
                        $content = $dialogues[array_rand($dialogues)] . "\n\n" . $content;
                    }
                } else {
                    $content = $hrpContents[array_rand($hrpContents)];
                }
                
                $post->setContent($content);
                $post->setThread($thread);
                $post->setType($threadType);
                
                // Choisir un auteur pour le post
                $postAuthor = $users[array_rand($users)];
                $post->setAuthor($postAuthor);
                
                // Pour les posts RP, assigner un personnage
                if ($threadType === 'roleplay') {
                    $userCharacters = array_filter($characters, function($char) use ($postAuthor) {
                        return $char->getOwner() === $postAuthor && $char->getStatus() === 'validated';
                    });
                    if (!empty($userCharacters)) {
                        $postCharacter = $userCharacters[array_rand($userCharacters)];
                        $post->setCharacter($postCharacter);
                    } elseif (!empty($characters)) {
                        // Fallback : n'importe quel personnage validé
                        $validCharacters = array_filter($characters, function($char) {
                            return $char->getStatus() === 'validated';
                        });
                        if (!empty($validCharacters)) {
                            $post->setCharacter($validCharacters[array_rand($validCharacters)]);
                        }
                    }
                }
                
                // Date du post (premier post = création du thread, autres après)
                if ($i === 0) {
                    $post->setCreatedAt($threadCreatedAt);
                } else {
                    // Posts suivants entre 1 heure et 7 jours après
                    $hoursAfter = rand(1, 168); // 168 heures = 7 jours
                    $post->setCreatedAt($threadCreatedAt->modify("+$hoursAfter hours"));
                }
                
                $manager->persist($post);
                
                // Mettre à jour updatedAt du thread avec le dernier post
                if ($post->getCreatedAt() > $thread->getUpdatedAt()) {
                    $thread->setUpdatedAt($post->getCreatedAt());
                }
            }
            
            $threadCount++;
            
            // Flush tous les 20 threads pour éviter les problèmes de mémoire
            if ($threadCount % 20 === 0) {
                $manager->flush();
                echo "Créé $threadCount threads...\n";
            }
        }

        $manager->flush();
        echo "Terminé ! $threadCount threads créés avec leurs posts.\n";
    }

    public function getDependencies()
    {
        return [
            UniverseFixtures::class,
            ForumCategoryFixtures::class,
            ForumFixtures::class,
            UserFixtures::class,
            CharacterFixtures::class,
            DCExtendedFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['main-fixtures'];
    }
}

