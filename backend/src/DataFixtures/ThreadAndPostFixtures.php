<?php

namespace App\DataFixtures;

use App\Entity\Thread;
use App\Entity\Post;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ThreadAndPostFixtures extends Fixture implements DependentFixtureInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $threads = [
            // Threads RP
            [
                'title' => 'Menace à Metropolis',
                'description' => 'Une nouvelle menace plane sur Metropolis. Superman pourra-t-il y faire face seul?',
                'forum' => 'forum_metropolis',
                'author' => 'user_clarkk',
                'type' => 'roleplay',
                'status' => 'open',
                'characterCreator' => 'character_superman',
                'location' => 'location_metropolis',
                'sticky' => false,
                'posts' => [
                    [
                        'content' => "<p>Clark Kent ajuste ses lunettes tout en consultant les dernières dépêches sur son ordinateur. Il fronce les sourcils en voyant une série d'incidents étranges reportés dans le quartier est de Metropolis.</p><p>\"Lois, tu as vu ces rapports d'incidents? Ça pourrait faire un bon sujet d'enquête\", dit-il en se tournant vers sa collègue.</p><p>Mais déjà, son ouïe surhumaine capte des cris de détresse au loin. Il se lève précipitamment.</p><p>\"Je... dois y aller. J'ai un rendez-vous avec une source pour l'article sur LexCorp. On se voit plus tard!\"</p><p>Quelques instants plus tard, Superman fend les airs au-dessus de Metropolis, se dirigeant vers la source des cris. Une étrange lueur verte émane d'un bâtiment abandonné...</p>",
                        'author' => 'user_clarkk',
                        'character' => 'character_superman',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Lois Lane observe Clark quitter précipitamment la salle de rédaction du Daily Planet. Elle soupire en secouant légèrement la tête, habituée à ces départs soudains.</p><p>\"Encore une 'source', hein Kent?\" murmure-t-elle pour elle-même.</p><p>Elle jette un œil à l'écran de son collègue et fronce les sourcils en voyant les rapports d'incidents. Son instinct de journaliste s'éveille immédiatement.</p><p>\"Perry!\" appelle-t-elle en se levant. \"Je pars enquêter sur ces événements étranges dans l'Est. Je sens qu'il y a un article à faire!\"</p><p>Sans attendre la réponse de son rédacteur en chef, Lois attrape son sac, son carnet de notes et se dirige vers l'ascenseur. Elle n'a pas besoin de super-pouvoirs pour sentir qu'une grande histoire se prépare.</p>",
                        'author' => 'user_dianap',
                        'character' => 'character_lois_lane',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Je vois qu'on s'amuse bien à Metropolis...</p><p>Lex Luthor observe les écrans de contrôle dans son bureau au sommet de la tour LexCorp. Les caméras de surveillance de la ville lui montrent Superman volant vers le quartier Est.</p><p>\"Parfait\", murmure-t-il avec un sourire satisfait. \"Tout se déroule comme prévu.\"</p><p>Il appuie sur l'interphone. \"Mercy, préparez ma voiture. Je vais faire un petit tour pour... observer les événements.\"</p><p>Il enfile sa veste en se dirigeant vers l'ascenseur privé. Le piège est en place, et le grand Scout va tomber dedans tête la première. La kryptonite synthétique qu'il a développée va enfin être testée en conditions réelles.</p>",
                        'author' => 'user_clarkk',
                        'character' => 'character_lex_luthor',
                        'isRoleplay' => true
                    ]
                ]
            ],
            [
                'title' => 'Chaos à Arkham',
                'description' => "Une évasion massive a lieu à l'asile d'Arkham. Batman et ses alliés doivent intervenir rapidement.",
                'forum' => 'subforum_arkham_asylum',
                'author' => 'user_brucew',
                'type' => 'roleplay',
                'status' => 'open',
                'characterCreator' => 'character_batman',
                'location' => 'location_arkham_asylum',
                'sticky' => false,
                'posts' => [
                    [
                        'content' => "<p>Les alarmes retentissent dans tout l'asile d'Arkham. Le bâtiment est plongé dans la pénombre, uniquement éclairé par les lumières rouges clignotantes du système d'urgence.</p><p>Batman se tient sur le toit d'un bâtiment adjacent, observant le chaos qui se déroule. Plusieurs détenus ont déjà réussi à s'échapper et courent dans les jardins de l'établissement.</p><p>\"Oracle, je suis sur place. La situation est critique. Envoie un message à Nightwing et Robin pour qu'ils me rejoignent. Il faut contenir cette évasion avant que les plus dangereux ne s'échappent.\"</p><p>Batman sort son grappin et s'élance vers l'entrée principale de l'asile. Les gardes semblent dépassés par la situation. Il aperçoit le commissaire Gordon qui tente d'organiser un périmètre de sécurité.</p><p>\"Gordon!\" appelle-t-il en atterrissant près de lui. \"Que s'est-il passé?\"</p>",
                        'author' => 'user_brucew',
                        'character' => 'character_batman',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>HAHAHAHAHAHAHAHA!</p><p>L'écho d'un rire démentiel résonne dans les couloirs sombres d'Arkham. Le Joker, vêtu de son habituel costume violet, gambade joyeusement parmi les cellules ouvertes, une télécommande à la main.</p><p>\"Oh, Batsy! Tu es venu jouer avec moi? Comme c'est gentil!\" s'exclame-t-il en apercevant le Chevalier Noir. \"J'ai préparé toute une soirée pour toi! Une petite fête de libération pour tous mes amis ici présents!\"</p><p>Il appuie sur un bouton de la télécommande, déclenchant une nouvelle série d'explosions dans l'aile Est. Des cris de panique s'élèvent.</p><p>\"Et ce n'est que le début de notre petite soirée!\" s'esclaffe-t-il avant de disparaître dans un nuage de fumée verte.</p>",
                        'author' => 'user_jokermad',
                        'character' => 'character_the_joker',
                        'isRoleplay' => true
                    ]
                ]
            ],
            [
                'title' => 'Réunion à la Tour de Garde',
                'description' => "La Justice League se réunit pour discuter d'une menace intergalactique imminente.",
                'forum' => 'subforum_tour_de_garde',
                'author' => 'user_clarkk',
                'type' => 'roleplay',
                'status' => 'open',
                'characterCreator' => 'character_superman',
                'location' => 'location_watchtower',
                'sticky' => true,
                'posts' => [
                    [
                        'content' => "<p>Superman se tient debout face à la grande baie vitrée de la Watchtower, contemplant la Terre qui tourne lentement en contrebas. Derrière lui, la grande table ronde de la Justice League attend l'arrivée des autres membres.</p><p>\"J'ai convoqué cette réunion d'urgence suite aux informations que nous avons reçues des Green Lanterns\", annonce-t-il en se tournant vers Batman, déjà présent. \"Une flotte de vaisseaux Thanagarians a été repérée en approche du système solaire. Leurs intentions ne sont pas claires, mais après l'invasion de l'année dernière...\"</p><p>Il laisse sa phrase en suspens, le regard grave. \"Nous devons nous préparer à toute éventualité.\"</p>",
                        'author' => 'user_clarkk',
                        'character' => 'character_superman',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Batman reste impassible, assis dans son fauteuil, les doigts croisés devant lui.</p><p>\"Si c'est bien une force d'invasion, nous aurons besoin de plus que juste les membres actuels de la Ligue\", déclare-t-il d'une voix grave. \"J'ai déjà contacté Zatanna et Doctor Fate pour renforcer notre défense magique.\"</p><p>Il active l'écran holographique devant lui, affichant les dernières images de la flotte Thanagarian captées par les satellites de Wayne Enterprises.</p><p>\"La configuration de leurs vaisseaux est différente de la dernière invasion. Ils semblent moins nombreux, mais plus lourdement armés. Nous avons 72 heures avant leur arrivée selon mes calculs.\"</p>",
                        'author' => 'user_brucew',
                        'character' => 'character_batman',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Wonder Woman entre dans la salle, son armure étincelant sous les lumières de la Tour de Garde. Elle salue d'un signe de tête Batman et Superman.</p><p>\"Les Amazones se tiennent prêtes à combattre si nécessaire\", annonce-t-elle en prenant place. \"Ma mère a ordonné la préparation de nos défenses, mais elle espère que la diplomatie pourra éviter un nouveau conflit.\"</p><p>Elle regarde l'écran montrant la flotte Thanagarian avec inquiétude.</p><p>\"Avons-nous essayé de communiquer avec eux? Peut-être ne sont-ils pas venus dans un but hostile.\"</p>",
                        'author' => 'user_dianap',
                        'character' => 'character_wonder_woman',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Flash arrive dans un éclair rouge, s'arrêtant net devant son siège.</p><p>\"Désolé pour le retard! J'étais en plein combat contre Captain Cold à Central City.\" Il prend rapidement connaissance de la situation en parcourant les rapports à super-vitesse.</p><p>\"Oh... des Thanagarians. Encore. C'est pas comme si on n'avait pas assez de problèmes sur Terre.\" Il se tourne vers Hal. \"Tes copains du Corps des Green Lantern ne peuvent pas s'en occuper avant qu'ils n'arrivent ici?\"</p>",
                        'author' => 'user_barrya',
                        'character' => 'character_the_flash',
                        'isRoleplay' => true
                    ],
                    [
                        'content' => "<p>Green Lantern lève les yeux au ciel face à la remarque de Flash.</p><p>\"Le secteur 2814 est sous ma protection, mais le Corps est actuellement mobilisé sur plusieurs fronts. Sinestro cause des problèmes dans trois secteurs différents.\" Hal fait apparaître grâce à son anneau une représentation holographique de la galaxie.</p><p>\"J'ai envoyé un message à Oa, mais ne comptez pas sur des renforts immédiats. Nous sommes en première ligne.\" Il désigne la flotte Thanagarian. \"J'ai déjà rencontré leur commandant lors d'une mission précédente. C'est un guerrier honorable, mais intransigeant. Si nous pouvons établir un contact, je me porte volontaire.\"</p>",
                        'author' => 'user_halj',
                        'character' => 'character_green_lantern',
                        'isRoleplay' => true
                    ]
                ]
            ],
            // Threads HRP
            [
                'title' => 'Bienvenue sur DC Earth Forum!',
                'description' => 'Présentation du forum et règles de base pour les nouveaux membres.',
                'forum' => 'forum_présentations_membres',
                'author' => 'user_dcadmin',
                'type' => 'discussion',
                'status' => 'open',
                'characterCreator' => null,
                'location' => null,
                'sticky' => true,
                'posts' => [
                    [
                        'content' => "<h3>Bienvenue sur DC Earth!</h3><p>Nous sommes ravis de vous accueillir sur notre forum de roleplay dans l'univers DC Comics. Que vous soyez fan de Superman, Batman, Wonder Woman ou d'autres personnages moins connus, vous trouverez votre place ici.</p><h4>Quelques règles de base:</h4><ul><li>Respectez les autres membres</li><li>Pas de spam ou de contenu inapproprié</li><li>Minimum 3 lignes par post RP</li><li>Suivez le canon établi par le forum</li><li>Amusez-vous!</li></ul><p>N'hésitez pas à vous présenter et à poser vos questions dans cette section.</p>",
                        'author' => 'user_dcadmin',
                        'character' => null,
                        'isRoleplay' => false
                    ],
                    [
                        'content' => "<p>Bonjour à tous! Je suis nouveau sur le forum et j'adore l'univers DC depuis toujours. J'ai hâte de pouvoir incarner un personnage ici et de participer aux histoires avec vous tous.</p><p>J'ai une question: est-ce qu'il y a une limite au nombre de personnages qu'on peut créer? Et comment se déroule la validation?</p><p>Merci d'avance pour vos réponses!</p>",
                        'author' => 'user_arthurc',
                        'character' => null,
                        'isRoleplay' => false
                    ],
                    [
                        'content' => "<p>Bienvenue parmi nous, ArthurC!</p><p>Pour répondre à ta question, tu peux créer jusqu'à 3 personnages pour commencer. Une fois que tu as été actif pendant au moins un mois, tu pourras demander à en créer davantage. La validation est généralement assez rapide (24-48h) tant que ton personnage respecte l'univers et les règles établies.</p><p>N'hésite pas si tu as d'autres questions!</p>",
                        'author' => 'user_dcadmin',
                        'character' => null,
                        'isRoleplay' => false
                    ]
                ]
            ],
            [
                'title' => 'Discussion: Le meilleur film DC de tous les temps?',
                'description' => "Débat convivial sur les films de l'univers DC. Venez défendre votre préféré!",
                'forum' => 'forum_actualités_dc_comics',
                'author' => 'user_barrya',
                'type' => 'discussion',
                'status' => 'open',
                'characterCreator' => null,
                'location' => null,
                'sticky' => false,
                'posts' => [
                    [
                        'content' => "<p>Salut à tous les fans!</p><p>Je me demandais quel est selon vous le meilleur film DC jamais réalisé? Personnellement, j'hésite entre The Dark Knight de Nolan et le premier Superman avec Christopher Reeve qui a quand même posé les bases du genre.</p><p>Et vous, quel est votre préféré et pourquoi?</p>",
                        'author' => 'user_barrya',
                        'character' => null,
                        'isRoleplay' => false
                    ],
                    [
                        'content' => "<p>Sans hésiter: THE DARK KNIGHT!</p><p>Heath Ledger en Joker reste la meilleure interprétation d'un vilain dans un film de super-héros. L'ambiance, le scénario, la réalisation... tout est parfait dans ce film. Il a réussi à élever le genre au niveau du cinéma \"sérieux\".</p><p>Je pense que c'est le film qui a changé la perception du grand public sur les films de super-héros.</p>",
                        'author' => 'user_jokermad',
                        'character' => null,
                        'isRoleplay' => false
                    ],
                    [
                        'content' => "<p>Je vais défendre Wonder Woman (2017) qui a été une vraie bouffée d'air frais. C'était le premier film DC centré sur une super-héroïne, et Gal Gadot a parfaitement incarné Diana. La scène du No Man's Land reste iconique!</p><p>Ce film combine action, émotion et messages profonds sur l'humanité. Pour moi, c'est un chef-d'œuvre qui a prouvé qu'un film de super-héroïne pouvait cartonner au box-office.</p>",
                        'author' => 'user_dianap',
                        'character' => null,
                        'isRoleplay' => false
                    ]
                ]
            ],
            [
                'title' => 'Guide de création de personnage',
                'description' => "Tout ce que vous devez savoir pour créer un personnage convaincant sur notre forum.",
                'forum' => 'forum_guide_du_débutant',
                'author' => 'user_moderator',
                'type' => 'discussion',
                'status' => 'open',
                'characterCreator' => null,
                'location' => null,
                'sticky' => true,
                'posts' => [
                    [
                        'content' => "<h3>Guide de création de personnage</h3><p>Bienvenue dans ce guide qui vous aidera à créer un personnage intéressant et équilibré pour vos aventures sur DC Earth.</p><h4>1. Choisir un type de personnage</h4><p>Vous pouvez créer:</p><ul><li>Un personnage officiel DC (Superman, Batman, etc.)</li><li>Un personnage original inspiré de l'univers DC</li><li>Un simple civil sans pouvoir</li></ul><h4>2. Définir ses pouvoirs et capacités</h4><p>Soyez raisonnable dans l'attribution des pouvoirs. Un personnage trop puissant n'est pas amusant à jouer et peut déséquilibrer l'univers.</p><h4>3. Créer son background</h4><p>L'histoire de votre personnage doit être cohérente avec l'univers DC Earth et expliquer ses motivations et sa personnalité.</p><h4>4. Soumettre pour validation</h4><p>Une fois votre fiche terminée, soumettez-la à l'équipe de modération qui vérifiera sa conformité.</p><p>N'hésitez pas à poser vos questions ci-dessous!</p>",
                        'author' => 'user_moderator',
                        'character' => null,
                        'isRoleplay' => false
                    ],
                    [
                        'content' => "<p>Merci pour ce guide! J'ai une question concernant les personnages officiels: est-ce qu'il y a une liste des personnages déjà pris? J'aimerais incarner Green Arrow mais je ne sais pas s'il est disponible.</p>",
                        'author' => 'user_harleyq',
                        'character' => null,
                        'isRoleplay' => false
                    ],
                    [
                        'content' => "<p>Bonne question! Nous avons une liste complète des personnages déjà pris dans la section \"Personnages DC\" du forum. Green Arrow est actuellement disponible, donc tu peux tout à fait soumettre une fiche pour lui!</p><p>N'oublie pas que même pour un personnage officiel, nous demandons une fiche détaillée qui montre que tu comprends bien le personnage et que tu as une idée de la direction que tu veux lui donner.</p>",
                        'author' => 'user_moderator',
                        'character' => null,
                        'isRoleplay' => false
                    ]
                ]
            ]
        ];

        foreach ($threads as $threadData) {
            $thread = new Thread();
            $thread->setTitle($threadData['title']);
            $thread->setDescription($threadData['description']);
            $thread->setForum($this->getReference($threadData['forum']));
            $thread->setAuthor($this->getReference($threadData['author']));
            $thread->setType($threadData['type']);
            $thread->setStatus($threadData['status']);
            $thread->setSticky($threadData['sticky']);
            $thread->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 60))));
            $thread->setUpdatedAt(new \DateTimeImmutable(sprintf('-%d hours', rand(1, 24))));
            $thread->setSlug($this->slugger->slug($threadData['title'])->lower());
            
            if ($threadData['characterCreator'] !== null) {
                $thread->setCharacterCreator($this->getReference($threadData['characterCreator']));
            }
            
            if ($threadData['location'] !== null) {
                echo "Looking for location reference: " . $threadData['location'] . "\n";
                $thread->setLocation($this->getReference($threadData['location']));
            }
            
            $manager->persist($thread);
            
            // Créer les posts pour ce thread
            foreach ($threadData['posts'] as $index => $postData) {
                $post = new Post();
                $post->setContent($postData['content']);
                $post->setAuthor($this->getReference($postData['author']));
                $post->setThread($thread);
                $post->setType($postData['isRoleplay'] ? 'roleplay' : 'normal');
                
                if ($postData['character'] !== null) {
                    $post->setCharacter($this->getReference($postData['character']));
                }
                
                // Le premier post est créé en même temps que le thread
                if ($index === 0) {
                    $post->setCreatedAt($thread->getCreatedAt());
                } else {
                    // Posts suivants un peu plus tard
                    $post->setCreatedAt(new \DateTimeImmutable($thread->getCreatedAt()->format('Y-m-d H:i:s') . sprintf(' +%d hours', rand(1, 48))));
                }
                
                $manager->persist($post);
            }
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            LocationFixtures::class,
            ForumFixtures::class,
            UserFixtures::class,
            CharacterFixtures::class
        ];
    }
} 