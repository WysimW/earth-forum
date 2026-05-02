<?php

namespace App\DataFixtures;

use App\Entity\Character;
use App\Entity\Faction;
use App\Entity\Thread;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Univers;
use App\Entity\Location;
use App\Entity\Forum;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DCExtendedFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    // Helper pour obtenir une entité par repository sans utiliser getReference
    private function getEntityByRepository(string $entityClass, array $criteria)
    {
        return $this->container->get('doctrine')->getRepository($entityClass)->findOneBy($criteria);
    }

    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();
        
        // Récupérer l'univers DC
        $dcUniverse = $manager->getRepository(Univers::class)->findOneBy(['name' => 'DC Comics']);
        
        if (!$dcUniverse) {
            throw new \Exception("L'univers DC Comics n'existe pas dans la base de données. Assurez-vous de charger les fixtures de base d'abord.");
        }
        
        // Repository pour les rôles
        $roleRepository = $manager->getRepository(Role::class);
        
        // 1. Création de nouveaux personnages pour les utilisateurs existants
        $newCharacters = [
            [
                'name' => 'Nightwing',
                'actualPseudo' => 'Dick Grayson',
                'avatar' => 'https://via.placeholder.com/300x300?text=Nightwing',
                'type' => 'officiel',
                'biography' => "Ancien Robin et protégé de Batman, Dick Grayson a évolué pour devenir son propre héros sous l'identité de Nightwing. Opérant principalement à Blüdhaven, il combine acrobaties et techniques de combat apprises auprès du Chevalier Noir.",
                'status' => 'validated',
                'abilities' => 'Acrobate de niveau olympique, expert en arts martiaux, détective compétent, bâtons d\'escrima électrifiés',
                'userPseudo' => 'BruceW',
                'roleName' => 'hero'
            ],
            [
                'name' => 'Catwoman',
                'actualPseudo' => 'Selina Kyle',
                'avatar' => 'https://via.placeholder.com/300x300?text=Catwoman',
                'type' => 'officiel',
                'biography' => "Voleuse professionnelle au grand cœur, Selina Kyle navigue dans la zone grise de la moralité. Tantôt ennemie, tantôt alliée de Batman, leur relation complexe est légendaire dans l'univers DC.",
                'status' => 'validated',
                'abilities' => 'Agilité féline, maîtrise du combat rapproché, experte en cambriolage, fouet',
                'userPseudo' => 'DianaP',
                'roleName' => 'antihero'
            ],
            [
                'name' => 'Deathstroke',
                'actualPseudo' => 'Slade Wilson',
                'avatar' => 'https://via.placeholder.com/300x300?text=Deathstroke',
                'type' => 'officiel',
                'biography' => "Mercenaire amélioré par des expériences militaires, Deathstroke est considéré comme l'assassin le plus dangereux du monde DC. Doté d'une intelligence tactique remarquable, il peut tenir tête aux plus grands héros.",
                'status' => 'validated',
                'abilities' => 'Force et réflexes surhumains, régénération accélérée, maîtrise de toutes les armes, génie tactique',
                'userPseudo' => 'JokerMad',
                'roleName' => 'villain'
            ],
            [
                'name' => 'Cyborg',
                'actualPseudo' => 'Victor Stone',
                'avatar' => 'https://via.placeholder.com/300x300?text=Cyborg',
                'type' => 'officiel',
                'biography' => "Suite à un accident terrible, Victor Stone a été sauvé par la technologie expérimentale de son père qui l'a transformé en un hybride homme-machine. Membre de la Justice League, il lutte pour conserver son humanité.",
                'status' => 'validated',
                'abilities' => 'Interface technologique, force surhumaine, canons soniques, vol, téléportation',
                'userPseudo' => 'BarryA',
                'roleName' => 'hero'
            ]
        ];

        // 2. Création de NPCs (personnages non-joueurs)
        $npcs = [
            [
                'name' => 'Oracle',
                'actualPseudo' => 'Barbara Gordon',
                'avatar' => 'https://via.placeholder.com/300x300?text=Oracle',
                'type' => 'npc',
                'biography' => "Ancienne Batgirl paralysée par le Joker, Barbara Gordon est devenue Oracle, une informaticienne de génie qui coordonne les opérations de la Bat-Family et fournit un support technique crucial aux héros.",
                'status' => 'validated',
                'abilities' => 'Génie en informatique, piratage avancé, coordination tactique, renseignement',
                'userPseudo' => null,
                'roleName' => 'hero'
            ],
            [
                'name' => 'Commissioner Gordon',
                'actualPseudo' => 'James Gordon',
                'avatar' => 'https://via.placeholder.com/300x300?text=Commissioner+Gordon',
                'type' => 'npc',
                'biography' => "Commissaire de police de Gotham City et allié de Batman, James Gordon est l'un des rares policiers honnêtes de la ville. Son intégrité et son courage en font un pilier essentiel dans la lutte contre le crime.",
                'status' => 'validated',
                'abilities' => 'Compétences policières, leadership, tireur expérimenté',
                'userPseudo' => null,
                'roleName' => 'civilian'
            ],
            [
                'name' => 'Amanda Waller',
                'actualPseudo' => 'Amanda Waller',
                'avatar' => 'https://via.placeholder.com/300x300?text=Amanda+Waller',
                'type' => 'npc',
                'biography' => "Directrice impitoyable de l'A.R.G.U.S. et créatrice de la Suicide Squad, Amanda Waller est prête à tout pour protéger l'Amérique. Sa détermination et sa volonté de fer en font une adversaire redoutable, même sans super-pouvoirs.",
                'status' => 'validated',
                'abilities' => 'Génie stratégique, manipulation, influence politique, ressources gouvernementales',
                'userPseudo' => null,
                'roleName' => 'antihero'
            ]
        ];

        // Repository pour les utilisateurs
        $userRepository = $manager->getRepository(User::class);

        // Fusion et création des personnages
        $allCharacters = array_merge($newCharacters, $npcs);
        $characterReferences = [];

        foreach ($allCharacters as $characterData) {
            $character = new Character();
            $character->setName($characterData['name']);
            $character->setActualPseudo($characterData['actualPseudo']);
            $character->setAvatar($characterData['avatar']);
            $character->setBiography($characterData['biography']);
            $character->setStatus($characterData['status']);
            $character->setAbilities($characterData['abilities']);
            $character->setUniverse($dcUniverse);
            
            if ($characterData['userPseudo'] !== null) {
                $user = $userRepository->findOneBy(['pseudo' => $characterData['userPseudo']]);
                if ($user) {
                    $character->setUser($user);
                }
            }
            
            // Trouver le rôle (à ignorer si la méthode setRole n'existe pas)
            // $role = $roleRepository->findOneBy(['name' => $characterData['roleName']]);
            
            // Génération du slug
            $slug = $slugger->slug(strtolower($characterData['name']))->toString();
            $character->setSlug($slug);
            
            $character->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 60))));
            $character->setValidatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 40))));

            $manager->persist($character);
            
            // Référence pour utilisation ultérieure
            $refName = 'dc_extended_character_' . strtolower(str_replace([' ', '.'], '_', $characterData['name']));
            $this->addReference($refName, $character);
            $characterReferences[] = $refName;
        }

        // Repository pour les lieux
        $locationRepository = $manager->getRepository(Location::class);
        
        // Récupérer Watchtower comme lieu par défaut
        $watchtower = $locationRepository->findOneBy(['name' => 'Tour de Garde']);
        
        // 3. Création de nouvelles factions
        $newFactions = [
            [
                'name' => 'Teen Titans',
                'description' => 'Équipe de jeunes super-héros formée initialement par les anciens acolytes des membres de la Justice League. Les Teen Titans combinent leurs compétences uniques pour affronter des menaces que même les héros adultes ne peuvent pas toujours gérer.',
                'alignment' => 'Héroïque',
                'scope' => 'National',
                'objectives' => 'Protéger le monde tout en aidant les jeunes héros à développer leurs capacités et à trouver leur place dans la communauté super-héroïque.',
                'headquartersName' => 'Tour de Garde', // Par défaut, utiliser Watchtower
                'founderPseudo' => 'BruceW',
                'characters' => [
                    'dc_extended_character_nightwing',
                    'dc_extended_character_cyborg'
                ],
                'logo' => 'https://via.placeholder.com/500x500?text=Teen+Titans',
                'icon' => 'https://via.placeholder.com/100x100?text=TT',
                'status' => Faction::STATUS_OPEN
            ],
            [
                'name' => 'Task Force X',
                'description' => 'Également connue sous le nom de Suicide Squad, cette équipe secrète est composée de criminels qui effectuent des missions dangereuses pour le gouvernement en échange de réductions de peine. Dirigée par Amanda Waller, l\'équipe est dispensable et souvent envoyée dans des situations impossibles.',
                'alignment' => 'Neutre',
                'scope' => 'National',
                'objectives' => 'Exécuter des opérations clandestines pour le gouvernement américain, souvent dans des contextes où la négation plausible est nécessaire.',
                'headquartersName' => 'Tour de Garde',
                'founderPseudo' => 'JokerMad',
                'characters' => [
                    'dc_extended_character_deathstroke',
                    'dc_extended_character_amanda_waller'
                ],
                'logo' => 'https://via.placeholder.com/500x500?text=Task+Force+X',
                'icon' => 'https://via.placeholder.com/100x100?text=TFX',
                'status' => Faction::STATUS_OPEN
            ],
            [
                'name' => 'Birds of Prey',
                'description' => 'Équipe principalement féminine opérant à Gotham City, dirigée par Oracle. Combinant intelligence, techniques de combat et diverses compétences spécialisées, les Birds of Prey s\'attaquent aux menaces que les autorités traditionnelles ne peuvent pas gérer.',
                'alignment' => 'Héroïque',
                'scope' => 'Local',
                'objectives' => 'Combattre le crime organisé et protéger les innocents à Gotham, en se concentrant particulièrement sur les cas impliquant des crimes contre les femmes.',
                'headquartersName' => 'Tour de Garde',
                'founderPseudo' => 'DianaP',
                'characters' => [
                    'dc_extended_character_catwoman',
                    'dc_extended_character_oracle'
                ],
                'logo' => 'https://via.placeholder.com/500x500?text=Birds+of+Prey',
                'icon' => 'https://via.placeholder.com/100x100?text=BoP',
                'status' => Faction::STATUS_OPEN
            ]
        ];

        $factionReferences = [];
        foreach ($newFactions as $factionData) {
            $faction = new Faction();
            $faction->setName($factionData['name']);
            $faction->setDescription($factionData['description']);
            $faction->setUniverse($dcUniverse);
            $faction->setAlignment($factionData['alignment']);
            $faction->setScope($factionData['scope']);
            $faction->setObjectives($factionData['objectives']);
            
            // Utiliser Watchtower par défaut
            $faction->setHeadquarters($watchtower);
            
            // Trouver le fondateur
            $founder = $userRepository->findOneBy(['pseudo' => $factionData['founderPseudo']]);
            if ($founder) {
                $faction->setFounder($founder);
            }
            
            $faction->setLogo($factionData['logo']);
            $faction->setIcon($factionData['icon']);
            $faction->setStatus($factionData['status']);
            
            // Ajout des personnages à la faction
            foreach ($factionData['characters'] as $characterRef) {
                try {
                    $character = $this->getReference($characterRef);
                    $faction->addCharacter($character);
                } catch (\Exception $e) {
                    // En cas de référence manquante, on continue sans ajouter ce personnage
                    continue;
                }
            }
            
            // Essayer d'ajouter Harley Quinn à certaines factions
            if ($factionData['name'] === 'Task Force X' || $factionData['name'] === 'Birds of Prey') {
                $harleyQuinn = $manager->getRepository(Character::class)->findOneBy(['name' => 'Harley Quinn']);
                if ($harleyQuinn) {
                    $faction->addCharacter($harleyQuinn);
                }
            }

            $manager->persist($faction);
            
            // Référence pour les threads
            $refName = 'dc_extended_faction_' . strtolower(str_replace([' ', '-', "'", '.', '&'], '_', $factionData['name']));
            $this->addReference($refName, $faction);
            $factionReferences[] = $refName;
        }

        // Repository pour les forums
        $forumRepository = $manager->getRepository(Forum::class);
        
        // 4. Création des threads et posts pour les factions
        $threads = [
            [
                'title' => 'Première mission des Teen Titans',
                'description' => 'Nightwing rassemble les Teen Titans pour leur première mission contre un nouvel ennemi à Jump City.',
                'forumName' => 'Batcave',
                'authorPseudo' => 'BruceW',
                'type' => 'roleplay',
                'status' => 'open',
                'characterCreatorName' => 'Nightwing',
                'locationName' => 'Tour de Garde',
                'factionName' => 'Teen Titans',
                'sticky' => false,
                'posts' => [
                    [
                        'content' => "<p>Nightwing se tient debout devant l'immense écran de la Tour des Titans, analysant les rapports d'activités suspectes à Jump City. Les alertes ont augmenté de 200% ces dernières semaines.</p><p>\"J'ai convoqué chacun d'entre vous parce que nous avons un problème majeur\", dit-il en se tournant vers les autres membres. \"H.I.V.E. a refait surface avec une nouvelle technologie de contrôle mental. Leurs agents ont déjà pris le contrôle de plusieurs installations stratégiques.\"</p><p>Il affiche plusieurs photos de civils aux yeux étrangement vides, agissant de manière robotique.</p><p>\"Nous devons localiser leur base et neutraliser ce dispositif. Cyborg, j'aurais besoin de ton expertise technique. Nous partons dans une heure.\"</p>",
                        'authorPseudo' => 'BruceW',
                        'characterName' => 'Nightwing',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Cyborg s'approche de l'écran, son œil cybernétique s'illuminant pendant qu'il analyse les données.</p><p>\"J'ai détecté des signatures énergétiques inhabituelles dans le district industriel\", annonce-t-il en projetant une carte 3D de la zone. \"Si je devais parier, je dirais que leur base est située dans l'ancienne usine de microprocesseurs WayneTech.\"</p><p>Il transforme son bras en canon sonique, vérifiant ses systèmes.</p><p>\"Mes scanners indiquent que la technologie utilisée pourrait être basée sur des ondes cérébrales modifiées. J'ai déjà commencé à travailler sur un inhibiteur qui pourrait bloquer le signal. Donnez-moi trente minutes pour le finaliser.\"</p>",
                        'authorPseudo' => 'BarryA',
                        'characterName' => 'Cyborg',
                        'isRoleplay' => true
                    ]
                ]
            ],
            [
                'title' => 'Infiltration à Belle Reve',
                'description' => 'Amanda Waller assigne à la Task Force X une mission d\'infiltration dans leur propre prison pour démasquer un agent double.',
                'forumName' => 'Arkham Asylum',
                'authorPseudo' => 'JokerMad',
                'type' => 'roleplay',
                'status' => 'open',
                'characterCreatorName' => 'Amanda Waller',
                'locationName' => 'Tour de Garde',
                'factionName' => 'Task Force X',
                'sticky' => false,
                'posts' => [
                    [
                        'content' => "<p>Le visage impassible d'Amanda Waller apparaît sur les écrans des cellules de chaque membre de la Task Force X.</p><p>\"Nous avons une situation critique\", annonce-t-elle d'une voix glaciale. \"Nos systèmes de sécurité ont été compromis de l'intérieur. Quelqu'un à Belle Reve fournit des informations à Checkmate, et nous ne savons pas qui.\"</p><p>Elle fait défiler plusieurs dossiers classifiés.</p><p>\"Votre mission est simple: vous allez retourner à Belle Reve en tant que prisonniers, identifier l'agent double et l'éliminer. Vous avez 48 heures. Dépassé ce délai, vos implants explosifs seront activés. Des questions?\"</p>",
                        'authorPseudo' => 'JokerMad',
                        'characterName' => 'Amanda Waller',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Deathstroke croise les bras, son unique œil visible fixant l'écran avec dédain.</p><p>\"Laissez-moi comprendre correctement\", répond-il d'une voix calme qui ne fait que souligner sa dangerosité. \"Vous voulez que nous retournions volontairement dans la prison dont nous essayons constamment de nous échapper, pour trouver un traître que vos propres agents de sécurité n'ont pas su identifier?\"</p><p>Il vérifie machinalement son équipement, même s'il sait qu'il sera confisqué pour la mission.</p><p>\"Quelles sont nos limitations opérationnelles? J'imagine que vous ne pouvez pas simplement retirer l'implant explosif de l'agent double de la liste des suspects?\"</p>",
                        'authorPseudo' => 'JokerMad',
                        'characterName' => 'Deathstroke',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Harley Quinn éclate de rire, se balançant dangereusement sur sa chaise.</p><p>\"Oh, c'est comme jouer à Cluedo, mais avec des explosifs dans le cou! J'ADORE ce jeu!\" s'exclame-t-elle en tapant dans ses mains avec excitation.</p><p>Elle se redresse soudainement, son expression devenant étrangement lucide.</p><p>\"Si c'est quelqu'un de l'intérieur, c'est forcément un garde ou un membre du personnel médical. Les prisonniers n'ont pas accès aux systèmes.\" Elle sourit largement. \"Et je connais TOUS les gardiens... certains très intimement. Je peux les faire parler, vous savez?\"</p>",
                        'authorPseudo' => 'HarleyQ',
                        'characterName' => 'Harley Quinn',
                        'isRoleplay' => true
                    ]
                ]
            ],
            [
                'title' => 'Opération: Ailes Nocturnes',
                'description' => 'Les Birds of Prey se réunissent pour planifier une opération visant à démanteler un réseau de trafic humain opérant à Gotham.',
                'forumName' => 'Gotham City',
                'authorPseudo' => 'DianaP',
                'type' => 'roleplay',
                'status' => 'open',
                'characterCreatorName' => 'Oracle',
                'locationName' => 'Tour de Garde',
                'factionName' => 'Birds of Prey',
                'sticky' => false,
                'posts' => [
                    [
                        'content' => "<p>L'image d'Oracle apparaît sur l'écran principal de la Clock Tower, son visage sérieux illuminé par la lueur des moniteurs qui l'entourent.</p><p>\"J'ai intercepté des communications cryptées entre plusieurs membres du réseau de Falcone. Ils ont établi un réseau de trafic humain qui utilise le port de Gotham comme plaque tournante\", explique-t-elle en affichant plusieurs documents et photos.</p><p>\"Selon mes informations, un navire transportant au moins trente personnes doit arriver demain soir au quai 27. Nous avons une chance d'intercepter l'opération et de sauver ces gens, tout en récupérant suffisamment de preuves pour faire tomber une partie du réseau.\"</p><p>Elle ajuste ses lunettes.</p><p>\"Catwoman, j'aurais besoin que tu infiltres le bureau de Falcone au Iceberg Lounge pour récupérer les documents du transfert. Harley, tu seras notre distraction si nécessaire.\"</p>",
                        'authorPseudo' => 'DianaP',
                        'characterName' => 'Oracle',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Catwoman étire nonchalamment ses bras, un sourire malicieux aux lèvres.</p><p>\"Infiltrer l'Iceberg Lounge? J'espérais presque quelque chose de difficile\", ronronne-t-elle en vérifiant son fouet. \"Je connais ce bâtiment comme ma poche, et Penguin a une fâcheuse tendance à utiliser les mêmes codes pour tous ses coffres-forts.\"</p><p>Elle étudie attentivement les plans que Oracle a affichés.</p><p>\"Je suggère une approche par le toit, en passant par les conduites de ventilation du troisième étage. La sécurité y est moins présente depuis que Cobblepot a réduit son budget.\" Elle ajuste ses lunettes infrarouges. \"Je peux entrer et sortir en moins de quinze minutes, sans que personne ne remarque ma présence. À condition que notre... distraction ne soit pas trop enthousiaste.\"</p><p>Elle jette un regard amusé vers Harley.</p>",
                        'authorPseudo' => 'DianaP',
                        'characterName' => 'Catwoman',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Harley Quinn fait tournoyer sa batte de baseball avec un enthousiasme à peine contenu.</p><p>\"Qu'est-ce que tu insinues, Minou? Que je ne sais pas être subtile?\" s'exclame-t-elle en feignant l'indignation avant d'éclater de rire. \"D'accord, j'avoue, la subtilité c'est pas trop mon truc!\"</p><p>Elle s'approche de l'écran, examinant les plans avec une concentration surprenante.</p><p>\"Je connais ces types. J'ai... travaillé avec certains d'entre eux quand j'étais avec Mistah J. Ils sont méfiants mais pas très malins.\" Elle sourit largement. \"Je pourrais faire une entrée SPECTACULAIRE du côté du casino - rien de tel qu'une ex-psychiatre cinglée avec une batte pour attirer l'attention! Pendant ce temps, notre féline préférée pourra se faufiler tranquillement.\"</p><p>Elle fait une pause, son expression devenant soudain plus sérieuse.</p><p>\"Ces ordures qui trafiquent des gens... ils méritent une bonne correction. Et j'suis VRAIMENT douée pour les corrections!\"</p>",
                        'authorPseudo' => 'HarleyQ',
                        'characterName' => 'Harley Quinn',
                        'isRoleplay' => true
                    ]
                ]
            ]
        ];

        // Repository pour les personnages
        $characterRepository = $manager->getRepository(Character::class);
        
        foreach ($threads as $threadData) {
            $thread = new Thread();
            $thread->setTitle($threadData['title']);
            $thread->setDescription($threadData['description']);
            
            // Définir explicitement elseworld à null pour éviter l'erreur de colonne manquante
            $thread->setElseworld(null);
            
            // Trouver le forum
            $forum = $forumRepository->findOneBy(['name' => $threadData['forumName']]);
            if (!$forum) {
                // Forum par défaut si non trouvé - chercher le premier forum RP
                $forum = $forumRepository->findOneBy(['isRoleplay' => true]);
            }
            if (!$forum) {
                // Si toujours pas trouvé, chercher n'importe quel forum
                $forum = $forumRepository->findOneBy([]);
            }
            if (!$forum) {
                throw new \Exception("Aucun forum trouvé dans la base de données. Assurez-vous que ForumFixtures est chargé avant DCExtendedFixtures.");
            }
            $thread->setForum($forum);
            
            // Trouver l'auteur
            $author = $userRepository->findOneBy(['pseudo' => $threadData['authorPseudo']]);
            if ($author) {
                $thread->setAuthor($author);
            }
            
            $thread->setType($threadData['type']);
            $thread->setStatus($threadData['status']);
            
            // Trouver le personnage créateur
            if ($threadData['characterCreatorName'] !== null) {
                $characterCreator = $characterRepository->findOneBy(['name' => $threadData['characterCreatorName']]);
                if ($characterCreator) {
                    $thread->setCharacterCreator($characterCreator);
                }
            }
            
            // Trouver le lieu
            if (isset($threadData['locationName']) && $threadData['locationName'] !== null) {
                $location = $locationRepository->findOneBy(['name' => $threadData['locationName']]);
                if ($location) {
                    $thread->setLocation($location);
                }
            }
            
            // Trouver la faction
            if (isset($threadData['factionName']) && $threadData['factionName'] !== null) {
                $faction = $manager->getRepository(Faction::class)->findOneBy(['name' => $threadData['factionName']]);
                if ($faction) {
                    $thread->addFaction($faction);
                }
            }
            
            $thread->setSticky($threadData['sticky']);
            $thread->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 30))));
            
            $manager->persist($thread);
            
            // Création des posts pour ce thread
            foreach ($threadData['posts'] as $index => $postData) {
                $post = new Post();
                $post->setContent($postData['content']);
                
                // Trouver l'auteur du post
                $postAuthor = $userRepository->findOneBy(['pseudo' => $postData['authorPseudo']]);
                if ($postAuthor) {
                    $post->setAuthor($postAuthor);
                }
                
                // Trouver le personnage du post
                if ($postData['characterName'] !== null) {
                    $postCharacter = $characterRepository->findOneBy(['name' => $postData['characterName']]);
                    if ($postCharacter) {
                        $post->setCharacter($postCharacter);
                    }
                }
                
                if ($postData['isRoleplay']) {
                    $post->setType('roleplay');
                } else {
                    $post->setType('normal');
                }
                $post->setThread($thread);
                $post->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 30 - $index))));
                
                $manager->persist($post);
            }
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UniverseFixtures::class,
            ForumCategoryFixtures::class,
            ForumFixtures::class,
            UserFixtures::class,
            CharacterFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['main-fixtures'];
    }
} 