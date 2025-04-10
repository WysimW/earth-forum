<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Forum;
use App\Entity\Thread;
use App\Entity\Character;
use App\Entity\ForumCategory;
use App\Entity\CategoriesType;
use App\Entity\CharacterRelation;
use App\Entity\Post;
use App\Entity\Location;
use App\Entity\Univers;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;
    private SluggerInterface $slugger;
    private array $users = [];
    private array $universes = [];
    private array $locations = [];
    private array $characters = [];
    private array $categoriesTypes = [];
    private array $forumCategories = [];
    private array $forums = [];

    public function __construct(UserPasswordHasherInterface $hasher, SluggerInterface $slugger)
    {
        $this->hasher = $hasher;
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $this->loadUniverses($manager);
        $this->loadLocations($manager);
        $this->loadCategoriesTypes($manager);
        $this->loadForumCategories($manager);
        $this->loadForums($manager);
        $this->loadCharacters($manager);
        $this->loadCharacterRelations($manager);
        $this->loadThreadsAndPosts($manager);
        
        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $userDatas = [
            ['pseudo' => 'Admin', 'email' => 'admin@example.com', 'roles' => ['ROLE_ADMIN', 'ROLE_MODERATOR'], 'password' => 'admin123'],
            ['pseudo' => 'Moderator', 'email' => 'moderator@example.com', 'roles' => ['ROLE_MODERATOR'], 'password' => 'mod123'],
            ['pseudo' => 'JohnDoe', 'email' => 'john@example.com', 'roles' => ['ROLE_USER'], 'password' => 'user123'],
            ['pseudo' => 'JaneSmith', 'email' => 'jane@example.com', 'roles' => ['ROLE_USER'], 'password' => 'user123'],
        ];

        foreach ($userDatas as $userData) {
            $user = new User();
            $user->setPseudo($userData['pseudo']);
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPassword($this->hasher->hashPassword($user, $userData['password']));
            $user->setAvatar('default-avatar.png');
            $manager->persist($user);
            $this->users[$userData['pseudo']] = $user;
        }
        $manager->flush();
    }

    private function loadUniverses(ObjectManager $manager): void
    {
        $universeData = [
            [
                'name' => 'Terre Alternative',
                'description' => 'Un monde où la magie et la technologie coexistent',
                'slug' => 'terre-alternative'
            ],
            [
                'name' => 'Neo Tokyo',
                'description' => 'Un univers cyberpunk futuriste',
                'slug' => 'neo-tokyo'
            ]
        ];

        foreach ($universeData as $data) {
            $universe = new Univers();
            $universe->setName($data['name']);
            $universe->setDescription($data['description']);
            $universe->setSlug($data['slug']);
            $manager->persist($universe);
            $this->universes[$data['name']] = $universe;
        }
        $manager->flush();
    }

    private function loadLocations(ObjectManager $manager): void
    {
        $locationData = [
            [
                'name' => 'Centre-ville magique',
                'description' => 'Le cœur de la ville où magie et technologie se rencontrent',
                'universe' => 'Terre Alternative',
                'city' => 'New Arcadia',
                'country' => 'République Magique',
                'parent' => null
            ],
            [
                'name' => 'District des Artisans',
                'description' => 'Zone où les artisans créent des artefacts magico-technologiques',
                'universe' => 'Terre Alternative',
                'city' => 'New Arcadia',
                'country' => 'République Magique',
                'parent' => 'Centre-ville magique'
            ],
            [
                'name' => 'Secteur 7',
                'description' => 'District high-tech de Neo Tokyo',
                'universe' => 'Neo Tokyo',
                'city' => 'Neo Tokyo',
                'country' => 'Japon Unifié',
                'parent' => null
            ]
        ];

        foreach ($locationData as $data) {
            $location = new Location();
            $location->setName($data['name']);
            $location->setDescription($data['description']);
            $location->setUniverse($this->universes[$data['universe']]);
            $location->setCity($data['city']);
            $location->setCountry($data['country']);
            if ($data['parent'] && isset($this->locations[$data['parent']])) {
                $location->setParent($this->locations[$data['parent']]);
            }
            $manager->persist($location);
            $this->locations[$data['name']] = $location;
        }
        $manager->flush();
    }

    private function loadCategoriesTypes(ObjectManager $manager): void
    {
        $types = ['Général', 'Roleplay', 'Organisation'];
        $now = new \DateTimeImmutable();
        
        foreach ($types as $type) {
            $categoryType = new CategoriesType();
            $categoryType->setName($type);
            $categoryType->setCreatedAt($now);
            $manager->persist($categoryType);
            $this->categoriesTypes[$type] = $categoryType;
        }
        $manager->flush();
    }

    private function loadForumCategories(ObjectManager $manager): void
    {
        $categoryData = [
            [
                'name' => 'Personnages',
                'description' => 'Création et gestion des personnages',
                'type' => 'Organisation',
                'position' => 0,
                'slug' => 'personnages'
            ],
            [
                'name' => 'Zones RP',
                'description' => 'Les différentes zones de roleplay',
                'type' => 'Roleplay',
                'position' => 1,
                'slug' => 'zones-rp'
            ],
            [
                'name' => 'Général',
                'description' => 'Discussions générales',
                'type' => 'Général',
                'position' => 2,
                'slug' => 'general'
            ]
        ];

        foreach ($categoryData as $data) {
            $category = new ForumCategory();
            $category->setName($data['name']);
            $category->setDescription($data['description']);
            $category->setType($this->categoriesTypes[$data['type']]);
            $category->setPosition($data['position']);
            $category->setSlug($data['slug']);
            $manager->persist($category);
            $this->forumCategories[$data['name']] = $category;
        }
        $manager->flush();
    }

    private function loadForums(ObjectManager $manager): void
    {
        $forumData = [
            [
                'name' => 'Fiches en attente',
                'description' => 'Fiches de personnages en cours de création ou en attente de validation',
                'category' => 'Personnages',
                'isRoleplay' => false,
                'slug' => 'fiches-en-attente'
            ],
            [
                'name' => 'Fiches validées',
                'description' => 'Fiches de personnages validées',
                'category' => 'Personnages',
                'isRoleplay' => false,
                'slug' => 'fiches-validees'
            ],
            [
                'name' => 'Fiches refusées',
                'description' => 'Fiches de personnages refusées ou abandonnées',
                'category' => 'Personnages',
                'isRoleplay' => false,
                'slug' => 'fiches-refusees'
            ],
            [
                'name' => 'Centre-ville magique',
                'description' => 'Zone RP du centre-ville',
                'category' => 'Zones RP',
                'location' => 'Centre-ville magique',
                'isRoleplay' => true,
                'slug' => 'centre-ville-magique'
            ],
            [
                'name' => 'Secteur 7',
                'description' => 'Zone RP du Secteur 7',
                'category' => 'Zones RP',
                'location' => 'Secteur 7',
                'isRoleplay' => true,
                'slug' => 'secteur-7'
            ]
        ];

        foreach ($forumData as $data) {
            $forum = new Forum();
            $forum->setName($data['name']);
            $forum->setDescription($data['description']);
            $forum->setCategory($this->forumCategories[$data['category']]);
            $forum->setIsRoleplay($data['isRoleplay']);
            if (isset($data['location'])) {
                $forum->setLocation($this->locations[$data['location']]);
            }
            $forum->setStatus('open');
            $forum->setSlug($data['slug']);
            $forum->setCreatedAt(new \DateTimeImmutable());
            $forum->setUpdatedAt(new \DateTime());
            $manager->persist($forum);
            $this->forums[$data['name']] = $forum;
        }
        $manager->flush();
    }

    private function loadCharacters(ObjectManager $manager): void
    {
        $characterData = [
            [
                'name' => 'Alex Thunder',
                'user' => 'JohnDoe',
                'universe' => 'Terre Alternative',
                'status' => Character::STATUS_VALIDATED,
                'biography' => 'Un mage technologique qui combine magie et science',
                'appearance' => 'Grand, cheveux argentés, yeux bleus électriques',
                'personality' => 'Curieux, méthodique, passionné par l\'innovation',
                'abilities' => 'Technomagie, Création d\'artefacts, Manipulation d\'énergie',
                'validatedAt' => true
            ],
            [
                'name' => 'Luna Shadow',
                'user' => 'JaneSmith',
                'universe' => 'Neo Tokyo',
                'status' => Character::STATUS_PENDING,
                'biography' => 'Une voleuse mystérieuse aux origines inconnues',
                'appearance' => 'Silhouette élancée, cheveux noirs, yeux violets',
                'personality' => 'Mystérieuse, rusée, indépendante',
                'abilities' => 'Furtivité, Manipulation des ombres, Acrobatie'
            ],
            [
                'name' => 'Marcus Steel',
                'user' => 'JohnDoe',
                'universe' => 'Neo Tokyo',
                'status' => Character::STATUS_REJECTED,
                'biography' => 'Un guerrier cyborg en quête de vengeance',
                'appearance' => 'Musclé, implants cybernétiques visibles',
                'personality' => 'Déterminé, vengeur, solitaire',
                'abilities' => 'Combat augmenté, Force surhumaine, Interface technologique'
            ]
        ];

        foreach ($characterData as $data) {
            $character = new Character();
            $character->setName($data['name']);
            $character->setUser($this->users[$data['user']]);
            $character->setUniverse($this->universes[$data['universe']]);
            $character->setStatus($data['status']);
            $character->setBiography($data['biography']);
            $character->setAppearance($data['appearance']);
            $character->setPersonality($data['personality']);
            $character->setAbilities($data['abilities']);
            if (isset($data['validatedAt']) && $data['validatedAt']) {
                $character->setValidatedAt(new \DateTimeImmutable());
            }
            $manager->persist($character);
            $this->characters[$data['name']] = $character;
        }
        $manager->flush();
    }

    private function loadCharacterRelations(ObjectManager $manager): void
    {
        $relationData = [
            [
                'source' => 'Alex Thunder',
                'target' => 'Luna Shadow',
                'type' => 'Rival',
                'description' => 'Une rivalité basée sur des idéologies différentes'
            ]
        ];

        foreach ($relationData as $data) {
            $relation = new CharacterRelation();
            $relation->setSourceCharacter($this->characters[$data['source']]);
            $relation->setTargetCharacter($this->characters[$data['target']]);
            $relation->setType($data['type']);
            $relation->setDescription($data['description']);
            $manager->persist($relation);
        }
        $manager->flush();
    }

    private function loadThreadsAndPosts(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();
        
        // Créer les threads de fiches de personnage
        foreach ($this->characters as $character) {
            $thread = new Thread();
            $thread->setTitle('Fiche de ' . $character->getName());
            $thread->setAuthor($character->getUser());
            $thread->setType('character_sheet');
            $thread->setCharacterSheet($character);
            $thread->setSlug($this->slugger->slug('fiche-' . $character->getName())->lower());
            $thread->setCreatedAt($now);
            $thread->setUpdatedAt($now);

            // Assigner le forum approprié selon le statut
            switch ($character->getStatus()) {
                case Character::STATUS_VALIDATED:
                    $thread->setForum($this->forums['Fiches validées']);
                    break;
                case Character::STATUS_REJECTED:
                    $thread->setForum($this->forums['Fiches refusées']);
                    break;
                default:
                    $thread->setForum($this->forums['Fiches en attente']);
            }

            $manager->persist($thread);

            // Créer le post initial avec le contenu de la fiche
            $initialPost = new Post();
            $initialPost->setThread($thread);
            $initialPost->setAuthor($character->getUser());
            $initialPost->setContent($this->generateCharacterSheetContent($character));
            $initialPost->setCreatedAt($now);
            $manager->persist($initialPost);

            // Si le personnage est validé ou refusé, ajouter un post de modération
            if (in_array($character->getStatus(), [Character::STATUS_VALIDATED, Character::STATUS_REJECTED])) {
                $moderationPost = new Post();
                $moderationPost->setThread($thread);
                $moderationPost->setAuthor($this->users['Moderator']);
                $moderationPost->setCreatedAt($now->modify('+1 minute'));
                $moderationPost->setContent(
                    $character->getStatus() === Character::STATUS_VALIDATED ?
                    '<div class="alert alert-success">Personnage validé ! Excellent travail sur le background.</div>' :
                    '<div class="alert alert-danger">Personnage refusé. Le concept nécessite des ajustements.</div>'
                );
                $manager->persist($moderationPost);
            }
        }

        // Créer quelques threads RP
        $rpThreadData = [
            [
                'title' => 'Une rencontre au clair de lune',
                'forum' => 'Centre-ville magique',
                'author' => 'JohnDoe',
                'character' => 'Alex Thunder',
                'content' => 'La nuit était claire sur New Arcadia, les néons magiques illuminaient les rues...'
            ],
            [
                'title' => 'Mystères dans le Secteur 7',
                'forum' => 'Secteur 7',
                'author' => 'JaneSmith',
                'character' => 'Luna Shadow',
                'content' => 'Les ombres du Secteur 7 cachaient bien des secrets...'
            ]
        ];

        foreach ($rpThreadData as $data) {
            $thread = new Thread();
            $thread->setTitle($data['title']);
            $thread->setForum($this->forums[$data['forum']]);
            $thread->setAuthor($this->users[$data['author']]);
            $thread->setType('roleplay');
            $thread->setCharacterCreator($this->characters[$data['character']]);
            $thread->setStatus('open');
            $thread->addParticipant($this->characters[$data['character']]);
            $thread->setSlug($this->slugger->slug($data['title'])->lower());
            $thread->setCreatedAt($now);
            $thread->setUpdatedAt($now);
            $manager->persist($thread);

            $post = new Post();
            $post->setThread($thread);
            $post->setAuthor($this->users[$data['author']]);
            $post->setCharacter($this->characters[$data['character']]);
            $post->setContent($data['content']);
            $post->setPostType('ic');
            $post->setCreatedAt($now);
            $manager->persist($post);
        }

        $manager->flush();
    }

    private function generateCharacterSheetContent(Character $character): string
    {
        return '
        <div class="character-sheet">
            <div class="character-header">
                <h2>' . $character->getName() . '</h2>
                <div class="universe-badge">
                    <i class="fas fa-globe"></i> ' . $character->getUniverse()->getName() . '
                </div>
            </div>

            <div class="section biography">
                <h3>Biographie</h3>
                ' . $character->getBiography() . '
            </div>

            <div class="section appearance">
                <h3>Apparence</h3>
                ' . $character->getAppearance() . '
            </div>

            <div class="section personality">
                <h3>Personnalité</h3>
                ' . $character->getPersonality() . '
            </div>

            <div class="section abilities">
                <h3>Capacités</h3>
                ' . $character->getAbilities() . '
            </div>
        </div>';
    }
}
