<?php

namespace App\DataFixtures;

use App\Entity\Forum;
use App\Entity\ForumCategory;
use App\Entity\Univers;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

class DCAbsoluteFixtures extends Fixture implements FixtureGroupInterface
{
    private const UNIVERSE_NAME = 'DC Absolute';
    private const UNIVERSE_SLUG = 'dc-absolute';
    private const FORUM_SLUG_PREFIX = 'dc-absolute-';

    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function load(ObjectManager $manager): void
    {
        $universe = $this->findOrCreateUniverse($manager);
        $categories = $this->resolveCategories($manager);

        foreach ($this->forumSections() as $section) {
            $category = $categories[$section['category']];
            $this->seedForumTree($manager, $universe, $category, $section['forums']);
        }

        $manager->flush();
    }

    private function findOrCreateUniverse(ObjectManager $manager): Univers
    {
        $repository = $manager->getRepository(Univers::class);
        $universe = $repository->findOneBy(['slug' => self::UNIVERSE_SLUG]);

        if (!$universe instanceof Univers) {
            $universe = new Univers();
            $universe->setName(self::UNIVERSE_NAME);
            $universe->setSlug(self::UNIVERSE_SLUG);
            $universe->setDescription(
                'Version moderne et radicale de DC : des héros réinventés dans un monde plus brut, '
                . 'politique et imprévisible, inspiré de la ligne éditoriale Absolute.'
            );
            $universe->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($universe);
        }

        return $universe;
    }

    /**
     * @return array<string, ForumCategory>
     */
    private function resolveCategories(ObjectManager $manager): array
    {
        $repository = $manager->getRepository(ForumCategory::class);
        $requiredSlugs = ['terre', 'univers', 'informations'];
        $resolved = [];

        foreach ($requiredSlugs as $slug) {
            $category = $repository->findOneBy(['slug' => $slug]);
            if (!$category instanceof ForumCategory) {
                throw new \RuntimeException(
                    sprintf("Categorie '%s' introuvable. Charge d'abord ForumCategoryFixtures.", $slug)
                );
            }

            $resolved[$slug] = $category;
        }

        return $resolved;
    }

    /**
     * @param array<int, array<string, mixed>> $forums
     */
    private function seedForumTree(
        ObjectManager $manager,
        Univers $universe,
        ForumCategory $defaultCategory,
        array $forums,
        ?Forum $parent = null
    ): void {
        foreach ($forums as $forumData) {
            $forum = $this->upsertForum($manager, $universe, $defaultCategory, $forumData, $parent);

            if (!empty($forumData['children']) && is_array($forumData['children'])) {
                $this->seedForumTree($manager, $universe, $defaultCategory, $forumData['children'], $forum);
            }
        }
    }

    /**
     * @param array<string, mixed> $forumData
     */
    private function upsertForum(
        ObjectManager $manager,
        Univers $universe,
        ForumCategory $category,
        array $forumData,
        ?Forum $parent
    ): Forum {
        $slug = self::FORUM_SLUG_PREFIX . $this->slugger->slug((string) $forumData['name'])->lower()->toString();
        $repository = $manager->getRepository(Forum::class);
        $forum = $repository->findOneBy(['slug' => $slug]);

        if (!$forum instanceof Forum) {
            $forum = new Forum();
            $forum->setSlug($slug);
            $manager->persist($forum);
        }

        $forum->setName((string) $forumData['name']);
        $forum->setDescription((string) $forumData['description']);
        $forum->setBanner(null);
        $forum->setHeroLogo(null);
        $forum->setCategory($category);
        $forum->setIsRoleplay((bool) $forumData['isRoleplay']);
        $forum->setType((string) $forumData['type']);
        $forum->setPosition((int) $forumData['position']);
        $forum->setUniverse($universe);
        $forum->setParent($parent);

        return $forum;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function forumSections(): array
    {
        return [
            [
                'category' => 'terre',
                'forums' => [
                    [
                        'name' => 'Metropolis Absolute',
                        'description' => 'Une Metropolis sous tension, entre populisme mediatique et surveillance privee.',
                        'isRoleplay' => true,
                        'type' => 'roleplay',
                        'position' => 1,
                        'children' => [
                            [
                                'name' => 'Daily Planet Reconstruit',
                                'description' => 'Une redaction inde fragile qui tente encore de documenter la verite.',
                                'isRoleplay' => true,
                                'type' => 'roleplay',
                                'position' => 1,
                            ],
                            [
                                'name' => 'District Zero',
                                'description' => 'Zone de crise placee sous controle securitaire permanent.',
                                'isRoleplay' => true,
                                'type' => 'roleplay',
                                'position' => 2,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Gotham Absolute',
                        'description' => 'Une ville encore plus opaque ou corruption institutionnelle et violence sociale explosent.',
                        'isRoleplay' => true,
                        'type' => 'roleplay',
                        'position' => 2,
                        'children' => [
                            [
                                'name' => 'Narrows Redline',
                                'description' => 'Quartiers abandonnes devenus terrains de guerre des factions.',
                                'isRoleplay' => true,
                                'type' => 'roleplay',
                                'position' => 1,
                            ],
                            [
                                'name' => 'Cour Criminelle',
                                'description' => 'Reseau clandestin qui arbitre les conflits du crime organise.',
                                'isRoleplay' => true,
                                'type' => 'roleplay',
                                'position' => 2,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Themyscira Reforgee',
                        'description' => 'Une Amazonie plus interventionniste, partagee entre diplomatie et doctrine martiale.',
                        'isRoleplay' => true,
                        'type' => 'roleplay',
                        'position' => 3,
                    ],
                    [
                        'name' => 'Central City Fracturee',
                        'description' => 'Laboratoires publics, accelerateurs en ruine et course contre des anomalies temporelles.',
                        'isRoleplay' => true,
                        'type' => 'roleplay',
                        'position' => 4,
                    ],
                ],
            ],
            [
                'category' => 'univers',
                'forums' => [
                    [
                        'name' => 'Apokolips Industrielle',
                        'description' => 'Machine imperiale de Darkseid, productivisme total et repression de masse.',
                        'isRoleplay' => true,
                        'type' => 'roleplay',
                        'position' => 1,
                    ],
                    [
                        'name' => 'New Genesis Dissidente',
                        'description' => 'Monde des Néo-Dieux progressistes, traverse par des luttes ideologiques internes.',
                        'isRoleplay' => true,
                        'type' => 'roleplay',
                        'position' => 2,
                    ],
                    [
                        'name' => 'La Trame Omniverselle',
                        'description' => 'Points de rupture entre realites Absolute, Elseworlds et lignes temporelles alterees.',
                        'isRoleplay' => true,
                        'type' => 'roleplay',
                        'position' => 3,
                        'children' => [
                            [
                                'name' => 'Observatoire des Moniteurs',
                                'description' => 'Cellule d analyse des crises multiverselles et des intrusions narratifs.',
                                'isRoleplay' => true,
                                'type' => 'roleplay',
                                'position' => 1,
                            ],
                            [
                                'name' => 'Archives des Terres Brisees',
                                'description' => 'Memoire des mondes effondres et des divergences majeures.',
                                'isRoleplay' => true,
                                'type' => 'roleplay',
                                'position' => 2,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'category' => 'informations',
                'forums' => [
                    [
                        'name' => 'Chroniques DC Absolute',
                        'description' => 'Actus editoriales, annonces lore et etats du canon de l univers Absolute.',
                        'isRoleplay' => false,
                        'type' => 'hrp',
                        'position' => 1,
                    ],
                    [
                        'name' => 'Guide de Continuite',
                        'description' => 'Reperes de timeline, points d entree et recap des arcs majeurs.',
                        'isRoleplay' => false,
                        'type' => 'hrp',
                        'position' => 2,
                    ],
                ],
            ],
        ];
    }

    public static function getGroups(): array
    {
        return ['dc-absolute-fixtures', 'append-fixtures'];
    }
}
