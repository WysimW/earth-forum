<?php

namespace App\DataFixtures;

use App\Entity\Thread;
use App\Entity\Post;
use App\Entity\Forum;
use App\Entity\User;
use App\Entity\Character;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class GuerreDuSangFixtures extends Fixture implements FixtureGroupInterface
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

        // Récupérer les utilisateurs nécessaires
        $userRepository = $manager->getRepository(User::class);
        $harleyUser = $userRepository->findOneBy(['pseudo' => 'HarleyQ']);
        if (!$harleyUser) {
            // Fallback : prendre n'importe quel utilisateur
            $harleyUser = $userRepository->findAll()[0] ?? null;
        }

        // Récupérer les personnages nécessaires
        $characterRepository = $manager->getRepository(Character::class);
        
        // Chercher ou créer un personnage pour Hécate/Katalin (NPC ou utiliser Harley Quinn)
        $hecateCharacter = $characterRepository->findOneBy(['name' => 'Harley Quinn']);
        if (!$hecateCharacter && $harleyUser) {
            // Si Harley Quinn n'existe pas, créer un NPC pour Hécate
            $hecateCharacter = new Character();
            $hecateCharacter->setName('Hécate');
            $hecateCharacter->setActualPseudo('Katalin Bathory');
            $hecateCharacter->setAvatar('https://via.placeholder.com/300x300?text=Hecate');
            $hecateCharacter->setBiography("Vampire et sorcière puissante, anciennement connue sous le nom de Katalin Bathory. Corrompue par les forces des enfers, elle utilise sa magie noire pour ouvrir des portails dimensionnels et invoquer des démons à Gotham City.");
            $hecateCharacter->setStatus('validated');
            $hecateCharacter->setAbilities('Magie noire, invocations démoniaques, immortalité vampirique, manipulation des ombres');
            $hecateCharacter->setUniverse($gothamCityForum->getUniverse());
            $hecateCharacter->setUser(null); // NPC
            $hecateCharacter->setSlug($this->slugger->slug('Hecate')->lower()->toString());
            $hecateCharacter->setCreatedAt(new \DateTimeImmutable('-30 days'));
            $hecateCharacter->setValidatedAt(new \DateTimeImmutable('-25 days'));
            $manager->persist($hecateCharacter);
            $manager->flush();
        }

        // Récupérer Cyborg
        $cyborgCharacter = $characterRepository->findOneBy(['name' => 'Cyborg']);
        if (!$cyborgCharacter) {
            throw new \Exception("Le personnage Cyborg n'existe pas. Assurez-vous que DCExtendedFixtures est chargé avant.");
        }

        // Récupérer d'autres membres de la Justice League pour les posts
        $supermanCharacter = $characterRepository->findOneBy(['name' => 'Superman']);
        $batmanCharacter = $characterRepository->findOneBy(['name' => 'Batman']);
        $wonderWomanCharacter = $characterRepository->findOneBy(['name' => 'Wonder Woman']);
        $flashCharacter = $characterRepository->findOneBy(['name' => 'The Flash']);

        // Créer le thread "La guerre du sang"
        $thread = new Thread();
        $thread->setTitle('La guerre du sang (Ft. Justice League)');
        $thread->setDescription('L\'Enfer s\'abat sur Gotham City. Une brèche magique est ouverte dans la ville, permettant à quantité de démons et de forces occultes d\'y parvenir enfin et libre.');
        $thread->setForum($gothamCityForum);
        $thread->setType('roleplay');
        $thread->setStatus('open');
        $thread->setSticky(false);
        $thread->setSlug($this->slugger->slug('La guerre du sang Ft Justice League')->lower()->toString());
        
        // Date de création : il y a 5 jours
        $thread->setCreatedAt(new \DateTimeImmutable('-5 days'));
        $thread->setUpdatedAt(new \DateTimeImmutable('-5 days'));
        
        // Auteur : utilisateur de Harley Quinn ou Hécate
        if ($harleyUser) {
            $thread->setAuthor($harleyUser);
        } else {
            $thread->setAuthor($userRepository->findAll()[0]);
        }
        
        // Character creator : Hécate
        if ($hecateCharacter) {
            $thread->setCharacterCreator($hecateCharacter);
        }
        
        $manager->persist($thread);

        // Post 1 : Hécate/Katalin (post initial)
        $post1 = new Post();
        $post1->setContent("<p>Hécate détacha son œil du télescope qui trônait au sommet du planétarium \"Saul Erdel\". C'était une grande bâtisse de style Art Déco, qu'affectionnait beaucoup l'ancienne Katalin. Mais la nouvelle, elle, l'avait choisie pour ses murs épais, le nombre restreint de ses accès et la tranquillité des lieux, car situé dans le nord de Gotham, dans le vaste Robinson Park.</p>

<p>Le soleil allait bientôt se lever, la vampire pouvait sentir jusque dans ses os. Et malgré le retour de ses pouvoirs magiques, combinés par ceux de son chère et tendre Sébastian, l'astre lumineux restait toujours une grande source d'inconfort. Mais le sang des enfers était puissant et la voyante avait trouvé le moyen de l'utiliser à ses propres fins.</p>

<p><em>\"Maîtresse Blood, c'est le moment\"</em> Fit l'adepte en se retirant, dos courbé, de quelques pas en signe de soumission éternelle.</p>

<p>Hécate percevait encore sur sa rétine la forme massive de la station spatiale où se regroupaient en ce moment même les justiciers. Ce petit diable de Klarion avait du sentir son regard peser sur lui. De quoi le faire réfléchir sur ses futures allégeances. Cette équipée était une tentative aussi pathétique que désespéré de l'arrêter. Mais si certains d'entre eux voulaient lui prêter allégeance, peut-être seraient-ils épargnés ?</p>

<p>Ses pensées la menèrent à s'asseoir sur un très beau fauteuil de facture Français avec, face à elle, une table de même facture. Et sur cette table, face cachée, posé en arc de cercle, son jeu de tarot. Les cartes étaient bien plus ancienne qu'elle-même, mais leurs papiers n'avaient pas jaunie et leurs bords ne s'étaient jamais écornés.</p>

<p>Passant sa main d'albâtre aux doigts fins sur le dos des cartes, elle en retourna deux au hasard. La voyante vouait une confiance aveugle en ces cartes. La première était \"la maison de Dieu\" alors que la seconde était \"la justice\". Hécate laissa couler sa puissance vers les deux cartes tout en indiquant aux adaptes où se rendre.</p>

<p><em>\"Le premier est sur le toit de l'asile d'Arkham. Défendez-le sur vos vies et tuer.\"</em> C'est sur ces paroles cryptiques qu'une faction importante d'adeptes encapuchonnés de rouge quittèrent le bâtiment pour se diriger vers le lieu indiqué. L'autre faction attendait sans un bruit la suite de la prophétie.</p>

<p><em>\"Le second est dans le hall du palais de justice. Défendez-le sur vos vies et tuer.\"</em></p>

<p>De la même manière que le premier groupe était parti en silence, le second groupe quitta le planétarium, laissant seule et sans défense la belle vampire.</p>

<p><em>\"Que puis-je faire pour vous Maîtresse ?\"</em> Demanda d'une voix anxieuse de plaire le dernier cultiste restant.</p>

<p><em>\"Place toi au centre du cercle et meurt pour moi\"</em> Fit la Hongroise dans un souffle. L'homme se plia en toute hâte aux désirs exprimés et quelques secondes après son trépas, son sang inonda les rigoles gravées dans le sol pour former un pentacle.</p>

<p>Dans un réflexe lié à son ancienne vie, Hécate se mit à genoux, les mains jointes dans une prière qui, pour un profane pouvait sembler sincère. Mais le fait qu'elle soit au centre d'en pentagramme ensanglanté dans une tenue qui ne rappelait en rien celle d'une nonne dénotait. Elle psalmodia de longues minutes sans cesse des mots impies qui finirent par se répercuter en cascade contre les murs vides de la salle et faire frissonner le sang qui composait le cercle.</p>

<p>Dans un mouvement involontaire, sa tête se jeta en arrière accompagné en cela d'un craquement d'os sinistre. Ses yeux s'ouvrirent, emplis de sang et sa magie ainsi que celle de Sabastian fût relâchée sur la ville.</p>

<p>Les habitants qui vaquaient à leurs occupations matinales ce jour-là ne comprirent pas immédiatement pourquoi la nuit avait de nouveau obscurci le jour. Pourquoi leur soleil n'était plus là, ni pourquoi une odeur de soufre semblait s'échapper des profondeurs de la ville. Depuis la station orbitale, le spectacle était tout autre. Une zone d'ombre semblait dévorer la ville de Gotham. En quelques instants, Cette gigantesque tache noireâtre prit la forme d'un cercle et des lignes rougeâtres finirent par apparaître, complétant le motif du pentagramme dans lequel priait Hécate. Mais celui-ci avait la proportion d'une ville.</p>

<p>Les réalités étaient stabilisées, les démons pouvaient maintenant aller et venir à leurs guises. D'abord les plus petits et les plus insignifiants, puis à mesure que le temps passerai, les plus forts et les plus puissants pourraient aussi changer de réalité. La vampire n'était que l'architecture de cette vaste machinerie magique. Elle n'avait pas la puissance nécessaire pour alimenter cela, mais les enfers si.</p>

<p>À cet instant précis, un vent violent fit voler les vitres de la grande salle en éclat, ébouriffa la ritualiste et retournant une seule carte de son tarot : Le Diable.</p>");
        $post1->setThread($thread);
        $post1->setType('roleplay');
        $post1->setCreatedAt(new \DateTimeImmutable('-5 days'));
        if ($harleyUser) {
            $post1->setAuthor($harleyUser);
        } else {
            $post1->setAuthor($userRepository->findAll()[0]);
        }
        if ($hecateCharacter) {
            $post1->setCharacter($hecateCharacter);
        }
        $manager->persist($post1);

        // Post 2 : Animateur Justice League (post HRP)
        $post2 = new Post();
        $post2->setContent("<p><strong>Prémices d'une lutte sanglante</strong></p>

<p>L'Enfer s'abat sur Gotham City.… ou plutôt, une brèche magique est ouverte dans la ville, permettant à quantité de démons et de forces occultes d'y parvenir enfin et libre.</p>

<p>Quelques minutes plus tôt, la Vampire Katalin Bathory a poursuivi le processus entamé en secret il y a peu, alors que la corruption de Brother Blood s'intensifie dans son corps. Ses mauvais penchants sont libérés, et elle entame alors une démarche visant à maudire, corrompre et damner pleinement la ville, tandis qu'elle a récupéré les adeptes de l'Eglise du Sang pour ses propres projets.</p>

<p>Une résistance s'organise cependant, et s'enclenche alors qu'un choc sonique fracasse le pan de la cité où elle opère.</p>

<p>Un Tunnel-Boum, le mode de téléportation des Néo-Dieux, s'ouvre sur la zone. Un vortex de transport permet ainsi à plusieurs super-héros et super-héroïnes d'arriver – les membres de la Justice League, accompagnés même avec de la chance par un Witch-Boy s'il confirme des valeurs d'entraide et de bienveillance qu'il ignorait sûrement chez lui.</p>

<p>Les protecteurs de ce monde débarquent, ainsi, et Cyborg est le premier à poser le pied sur la ville attaqué...</p>");
        $post2->setThread($thread);
        $post2->setType('hrp');
        $post2->setCreatedAt(new \DateTimeImmutable('-5 days +2 hours'));
        // Post HRP sans personnage, utiliser un utilisateur admin ou modérateur
        $adminUser = $userRepository->findOneBy(['pseudo' => 'admin']) ?? $userRepository->findAll()[0];
        $post2->setAuthor($adminUser);
        $manager->persist($post2);

        // Post 3 : Cyborg
        $cyborgUser = $cyborgCharacter->getUser();
        if ($cyborgUser) {
            $post3 = new Post();
            $post3->setContent("<p>Cyborg atterrit lourdement sur le sol de Gotham, ses systèmes d'atterrissage amortissant l'impact avec un grondement mécanique. Ses scanners s'activent immédiatement, analysant l'environnement corrompu qui l'entoure.</p>

<p>\"Mes capteurs détectent des anomalies dimensionnelles majeures\", annonce-t-il d'une voix grave, son œil cybernétique balayant l'horizon. \"La structure magique qui entoure la ville dépasse toutes les lectures que j'ai jamais enregistrées. C'est comme si les barrières entre notre réalité et les enfers avaient été complètement effacées.\"</p>

<p>Il transforme son bras en canon sonique, vérifiant ses systèmes d'armement.</p>

<p>\"Je détecte plusieurs points d'infiltration démoniaque. Le plus important semble être concentré dans le nord de la ville, près du Robinson Park. C'est là que la source de cette corruption semble prendre racine.\"</p>

<p>Il se tourne vers les autres membres de la League qui arrivent derrière lui.</p>

<p>\"Nous devons agir rapidement. Chaque minute qui passe permet à plus de créatures infernales de traverser. Je peux créer un champ de force temporaire pour protéger les civils, mais il faudra que quelqu'un s'occupe de fermer cette brèche à la source.\"</p>");
            $post3->setThread($thread);
            $post3->setType('roleplay');
            $post3->setCreatedAt(new \DateTimeImmutable('-5 days +3 hours'));
            $post3->setAuthor($cyborgUser);
            $post3->setCharacter($cyborgCharacter);
            $manager->persist($post3);
        }

        // Post 4 : Superman (si disponible)
        if ($supermanCharacter && $supermanCharacter->getUser()) {
            $post4 = new Post();
            $post4->setContent("<p>Superman descend du ciel dans un éclair rouge et bleu, atterrissant aux côtés de Cyborg avec un impact qui fait trembler le sol. Ses yeux brillent d'une lueur inquiétante alors qu'il scrute l'obscurité qui enveloppe Gotham.</p>

<p>\"J'ai survolé la ville entière\", déclare-t-il d'une voix grave. \"Cette magie noire s'étend comme une tumeur. Les civils sont terrifiés, beaucoup sont déjà corrompus par l'influence démoniaque.\"</p>

<p>Il serre les poings, sa cape flottant dans l'air chargé d'énergie maléfique.</p>

<p>\"Nous devons diviser nos forces. Certains d'entre nous doivent protéger les innocents, tandis que d'autres doivent s'attaquer directement à la source de cette corruption. Cyborg, tu as raison – le Robinson Park semble être l'épicentre. C'est là que nous devons frapper.\"</p>

<p>Il lève les yeux vers le ciel, où les étoiles sont masquées par une brume infernale.</p>

<p>\"Mais attention. Cette sorcière – Hécate – elle a prévu notre arrivée. J'ai vu des groupes d'adeptes se positionner à des points stratégiques. Arkham Asylum, le Palais de Justice... Elle prépare quelque chose de plus grand.\"</p>");
            $post4->setThread($thread);
            $post4->setType('roleplay');
            $post4->setCreatedAt(new \DateTimeImmutable('-5 days +4 hours'));
            $post4->setAuthor($supermanCharacter->getUser());
            $post4->setCharacter($supermanCharacter);
            $manager->persist($post4);
        }

        // Post 5 : Batman (si disponible)
        if ($batmanCharacter && $batmanCharacter->getUser()) {
            $post5 = new Post();
            $post5->setContent("<p>Batman émerge des ombres, sa silhouette sombre se détachant contre la lueur infernale qui émane de la ville. Il s'approche du groupe sans faire de bruit, son regard perçant analysant chaque détail de la situation.</p>

<p>\"J'ai étudié les rapports d'activité mystique de ces dernières semaines\", dit-il d'une voix rauque. \"Katalin Bathory – ou Hécate, comme elle se fait appeler maintenant – n'a pas agi seule. Elle a été corrompue par une entité connue sous le nom de Brother Blood. Leur alliance est ce qui rend cette menace si dangereuse.\"</p>

<p>Il active un écran holographique sur son gant, affichant des cartes de tarot et des symboles occultes.</p>

<p>\"Les deux points qu'elle a mentionnés – Arkham et le Palais de Justice – ne sont pas choisis au hasard. Ce sont des lieux de pouvoir symbolique. Elle utilise la géographie de Gotham comme composant de son rituel. Si nous voulons arrêter cela, nous devons briser le pentagramme qu'elle a tracé.\"</p>

<p>Il ferme l'hologramme et se tourne vers les autres.</p>

<p>\"Je vais à Arkham. J'y connais les tunnels mieux que quiconque. Wonder Woman, Flash – vous devriez vous rendre au Palais de Justice. Le reste de vous, avec Superman et Cyborg, allez directement au Robinson Park et affrontez Hécate.\"</p>");
            $post5->setThread($thread);
            $post5->setType('roleplay');
            $post5->setCreatedAt(new \DateTimeImmutable('-5 days +5 hours'));
            $post5->setAuthor($batmanCharacter->getUser());
            $post5->setCharacter($batmanCharacter);
            $manager->persist($post5);
        }

        // Post 6 : Wonder Woman (si disponible)
        if ($wonderWomanCharacter && $wonderWomanCharacter->getUser()) {
            $post6 = new Post();
            $post6->setContent("<p>Wonder Woman s'avance, son armure dorée brillant faiblement dans la pénombre magique. Son lasso de vérité émet une lueur douce, réagissant à la corruption qui l'entoure.</p>

<p>\"Cette magie... elle est ancienne\", murmure-t-elle, ses sens d'Amazone percevant les courants d'énergie maléfique. \"Plus ancienne que moi, même. Elle vient des profondeurs des enfers, d'un endroit où même les dieux hésitent à s'aventurer.\"</p>

<p>Elle serre son lasso, sentant sa puissance divine résister à l'influence démoniaque.</p>

<p>\"Batman a raison. Nous devons diviser nos forces, mais nous devons aussi être unis dans notre objectif. Cette sorcière compte sur notre division pour nous vaincre. Nous ne pouvons pas lui donner cette satisfaction.\"</p>

<p>Elle se tourne vers Flash.</p>

<p>\"Flash, avec ta vitesse, tu peux évacuer les civils des zones les plus dangereuses. Moi, je vais au Palais de Justice avec Batman. Mon lasso peut peut-être briser les enchantements qui y sont tissés.\"</p>

<p>Elle lève son bouclier, prête au combat.</p>

<p>\"Que les dieux nous protègent tous.\"</p>");
            $post6->setThread($thread);
            $post6->setType('roleplay');
            $post6->setCreatedAt(new \DateTimeImmutable('-5 days +6 hours'));
            $post6->setAuthor($wonderWomanCharacter->getUser());
            $post6->setCharacter($wonderWomanCharacter);
            $manager->persist($post6);
        }

        // Post 7 : Flash (si disponible)
        if ($flashCharacter && $flashCharacter->getUser()) {
            $post7 = new Post();
            $post7->setContent("<p>Flash apparaît dans un éclair d'électricité rouge, ses pieds laissant des traînées de foudre dans son sillage. Il s'arrête net devant le groupe, son costume vibrant encore de l'énergie de sa course.</p>

<p>\"J'ai déjà fait trois tours de la ville\", annonce-t-il, reprenant son souffle. \"Les civils sont paniqués, mais la plupart sont encore sains et saufs. J'ai évacué ceux qui étaient dans les zones les plus proches de la brèche.\"</p>

<p>Il fait une pause, son visage s'assombrissant.</p>

<p>\"Mais il y a quelque chose de bizarre. Les démons... ils ne sont pas tous hostiles. Certains semblent juste... perdus. Comme s'ils ne savaient pas pourquoi ils étaient là.\"</p>

<p>Il se tourne vers Wonder Woman.</p>

<p>\"Tu as raison, Diana. Je peux continuer à évacuer les civils pendant que vous vous occupez de la source. Mais faites attention – j'ai vu des choses là-bas, près du Robinson Park. Des choses que je n'aimerais pas revoir.\"</p>

<p>Il se prépare à repartir, l'électricité recommençant à crépiter autour de lui.</p>

<p>\"Si vous avez besoin de moi, appelez. Je serai là en une seconde.\"</p>");
            $post7->setThread($thread);
            $post7->setType('roleplay');
            $post7->setCreatedAt(new \DateTimeImmutable('-5 days +7 hours'));
            $post7->setAuthor($flashCharacter->getUser());
            $post7->setCharacter($flashCharacter);
            $manager->persist($post7);
        }

        // Mettre à jour la date de mise à jour du thread avec le dernier post
        $lastPostDate = new \DateTimeImmutable('-5 days +7 hours');
        $thread->setUpdatedAt($lastPostDate);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['append-fixtures']; // Groupe différent pour permettre le chargement en append
    }
}

