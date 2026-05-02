<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\Thread;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Repository\UserRepository;
use App\Repository\CharacterRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class RandomPostsFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private SluggerInterface $slugger;
    private ForumRepository $forumRepository;
    private ThreadRepository $threadRepository;
    private UserRepository $userRepository;
    private CharacterRepository $characterRepository;

    public function __construct(
        SluggerInterface $slugger,
        ForumRepository $forumRepository,
        ThreadRepository $threadRepository,
        UserRepository $userRepository,
        CharacterRepository $characterRepository
    ) {
        $this->slugger = $slugger;
        $this->forumRepository = $forumRepository;
        $this->threadRepository = $threadRepository;
        $this->userRepository = $userRepository;
        $this->characterRepository = $characterRepository;
    }

    public function load(ObjectManager $manager): void
    {
        // Liste de messages RP génériques
        $rpMessages = [
            "<p>*regarde autour de soi, l'air méfiant*</p><p>Je ne m'attendais pas à vous trouver ici. Les choses ont bien changé depuis notre dernière rencontre, n'est-ce pas ?</p><p>*s'approche lentement*</p><p>Nous devons parler. La situation est plus grave que nous le pensions.</p>",
            
            "<p>*entre dans la pièce d'un pas déterminé*</p><p>Les informations que j'ai récoltées sont troublantes. Il semblerait que quelqu'un cherche à déstabiliser la ville entière.</p><p>*pose un dossier sur la table*</p><p>Regardez par vous-même. Ces incidents ne peuvent pas être une coïncidence.</p>",
            
            "<p>*soupire profondément, visiblement fatigué*</p><p>Cela fait maintenant trois jours que je suis cette piste. Je commence à croire que nous tournons en rond.</p><p>*s'assoit lourdement*</p><p>Peut-être devrions-nous reconsidérer notre approche ?</p>",
            
            "<p>*lève les yeux au ciel en entendant une explosion au loin*</p><p>Encore ? Cette ville ne connaît donc jamais le repos...</p><p>*ajuste son équipement*</p><p>Je suppose que notre conversation devra attendre. Le devoir m'appelle.</p>",
            
            "<p>*observe la ville depuis les hauteurs*</p><p>Parfois je me demande si tout cela en vaut vraiment la peine. Si ce que nous faisons change réellement les choses.</p><p>*sourit légèrement*</p><p>Puis je vois des gens comme vous, et je me rappelle pourquoi nous continuons à nous battre.</p>",
            
            "<p>*fronce les sourcils en examinant les indices*</p><p>Ce n'est pas logique. Les empreintes s'arrêtent net ici, comme si notre suspect s'était... envolé.</p><p>*se redresse*</p><p>Je crains que nous n'ayons affaire à quelqu'un de bien plus dangereux que prévu.</p>",
            
            "<p>*entre précipitamment*</p><p>Nous n'avons plus beaucoup de temps ! Les systèmes de sécurité ont été compromis et...</p><p>*s'interrompt en remarquant votre présence*</p><p>Oh. Je ne savais pas que vous étiez déjà au courant.</p>",
            
            "<p>*sourit avec confiance*</p><p>Ne vous inquiétez pas. J'ai déjà affronté bien pire. Ce n'est qu'une autre menace parmi tant d'autres, et ensemble, nous la surmonterons.</p><p>*tends la main*</p><p>Alors, êtes-vous avec moi ?</p>",
            
            "<p>*contemple le ciel nocturne*</p><p>Dans des moments comme celui-ci, je repense à tout le chemin parcouru. À tout ce que nous avons sacrifié.</p><p>*tourne la tête vers vous*</p><p>Mais quand je vois ce que nous avons accompli ensemble, je sais que ça en valait la peine.</p>",
            
            "<p>*manipule un gadget high-tech*</p><p>Cette nouvelle technologie devrait nous donner un avantage considérable. Si mes calculs sont corrects, nous pourrons intercepter leur signal et...</p><p>*le dispositif émet des étincelles*</p><p>Eh bien, retour à la planche à dessin, on dirait.</p>"
        ];

        // Nombre total de posts à créer
        $totalPostsToCreate = 50;
        
        // Récupérer tous les utilisateurs existants
        $users = $this->userRepository->findAll();
        if (empty($users)) {
            throw new \RuntimeException('Aucun utilisateur trouvé en base de données');
        }
        
        // Récupérer tous les forums RP existants
        $roleplayForums = $this->forumRepository->findBy(['isRoleplay' => true]);
        if (empty($roleplayForums)) {
            throw new \RuntimeException('Aucun forum RP trouvé en base de données');
        }
        
        // Récupérer tous les threads RP existants
        $roleplayThreads = $this->threadRepository->findBy(['type' => 'roleplay']);
        
        // Récupérer tous les personnages existants
        $allCharacters = $this->characterRepository->findAll();
        if (empty($allCharacters)) {
            throw new \RuntimeException('Aucun personnage trouvé en base de données');
        }
        
        // Création d'un tableau associant les utilisateurs à leurs personnages
        $userCharacters = [];
        foreach ($users as $user) {
            $userCharacters[$user->getId()] = [];
        }
        
        foreach ($allCharacters as $character) {
            if ($character->getUser() !== null) {
                $userId = $character->getUser()->getId();
                $userCharacters[$userId][] = $character;
            }
        }
        
        for ($i = 0; $i < $totalPostsToCreate; $i++) {
            // Choisir un utilisateur aléatoire
            $randomUser = $users[array_rand($users)];
            $userId = $randomUser->getId();
            
            // Vérifier si l'utilisateur a des personnages
            if (empty($userCharacters[$userId])) {
                continue; // Passer à l'itération suivante
            }
            
            // Choisir un personnage aléatoire parmi ceux de l'utilisateur
            $randomCharacter = $userCharacters[$userId][array_rand($userCharacters[$userId])];
            
            // Décider si on crée un nouveau thread (10%) ou si on répond à un thread existant (90%)
            $createNewThread = (mt_rand(1, 100) <= 10);
            
            if ($createNewThread && !empty($roleplayForums)) {
                // Créer un nouveau thread dans un forum RP aléatoire
                $randomForum = $roleplayForums[array_rand($roleplayForums)];
                
                $thread = new Thread();
                $thread->setTitle('Scène RP: ' . $this->generateRandomRPTitle());
                $thread->setDescription('Une nouvelle scène de roleplay impliquant ' . $randomCharacter->getName());
                $thread->setForum($randomForum);
                $thread->setAuthor($randomUser);
                $thread->setType('roleplay');
                $thread->setStatus('open');
                $thread->setCharacterCreator($randomCharacter);
                $thread->setSticky(false);
                $thread->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 30))));
                $thread->setUpdatedAt(new \DateTimeImmutable(sprintf('-%d hours', rand(1, 24))));
                $thread->setSlug($this->slugger->slug($thread->getTitle())->lower());
                
                $manager->persist($thread);
                
                // Créer le premier post du thread
                $post = new Post();
                $post->setContent($rpMessages[array_rand($rpMessages)]);
                $post->setAuthor($randomUser);
                $post->setThread($thread);
                $post->setType('roleplay');
                $post->setCharacter($randomCharacter);
                $post->setCreatedAt($thread->getCreatedAt());
                
                $manager->persist($post);
            } elseif (!empty($roleplayThreads)) {
                // Répondre à un thread RP existant
                $randomThread = $roleplayThreads[array_rand($roleplayThreads)];
                
                $post = new Post();
                $post->setContent($rpMessages[array_rand($rpMessages)]);
                $post->setAuthor($randomUser);
                $post->setThread($randomThread);
                $post->setType('roleplay');
                $post->setCharacter($randomCharacter);
                $post->setCreatedAt(new \DateTimeImmutable(sprintf('-%d hours', rand(1, 24 * 30))));
                
                $manager->persist($post);
                
                // Mettre à jour la date de dernière modification du thread
                if ($post->getCreatedAt() > $randomThread->getUpdatedAt()) {
                    $randomThread->setUpdatedAt($post->getCreatedAt());
                    $manager->persist($randomThread);
                }
            }
        }
        
        $manager->flush();
    }
    
    private function generateRandomRPTitle(): string
    {
        $adjectives = ['Mystérieux', 'Sombre', 'Dangereux', 'Inattendu', 'Secret', 'Étrange', 'Troublant', 'Fatidique', 'Ultime', 'Crucial'];
        $nouns = ['Rencontre', 'Confrontation', 'Alliance', 'Mission', 'Défi', 'Combat', 'Révélation', 'Découverte', 'Menace', 'Événement'];
        $locations = ['à Metropolis', 'dans les rues de Gotham', 'au QG de la Justice League', 'à la Tour de Garde', 'dans les profondeurs d\'Atlantis', 'à Themyscira', 'dans l\'ombre', 'sous la pluie', 'au crépuscule', 'à l\'aube'];
        
        $adjective = $adjectives[array_rand($adjectives)];
        $noun = $nouns[array_rand($nouns)];
        $location = $locations[array_rand($locations)];
        
        return "Une $adjective $noun $location";
    }

    public function getDependencies()
    {
        return [
            ForumFixtures::class,
            CharacterFixtures::class,
            UserFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['main-fixtures'];
    }
} 