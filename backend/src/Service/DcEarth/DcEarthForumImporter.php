<?php

namespace App\Service\DcEarth;

use App\Entity\Forum;
use App\Entity\ForumCategory;
use App\Entity\Univers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DcEarthForumImporter
{
    /** @var array<string, mixed> */
    private array $config;

    /** @var array<string, Forum> */
    private array $forumBySourceSlug = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $configFile,
    ) {
    }

    /**
     * @return array{created: int, updated: int, skipped: int, categories: int}
     */
    public function import(string $sourceFile, SymfonyStyle $io, bool $dryRun = false): array
    {
        $this->config = $this->loadConfig();
        $payload = $this->loadSourceFile($sourceFile);

        $universe = $this->resolveUniverse();
        $categoryMap = $this->resolveCategories();

        $entries = $this->collectEntries($payload);
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'categories' => 0];

        $io->section(sprintf('Import structure forums DC-Earth (%d forums)', count($entries)));

        foreach ($entries as $entry) {
            if ($entry['parent_source_slug'] !== null && !isset($this->forumBySourceSlug[$entry['parent_source_slug']])) {
                ++$stats['skipped'];
                $io->warning(sprintf(
                    'Parent introuvable pour %s (%s) — forum ignoré.',
                    $entry['name'],
                    $entry['source_slug'],
                ));
                continue;
            }

            $categorySlug = $entry['category_slug'];
            if (!isset($categoryMap[$categorySlug])) {
                throw new \RuntimeException(sprintf("Catégorie '%s' introuvable en base.", $categorySlug));
            }

            $category = $categoryMap[$categorySlug];
            $parent = $entry['parent_source_slug'] !== null
                ? $this->forumBySourceSlug[$entry['parent_source_slug']]
                : null;

            $forum = $this->entityManager->getRepository(Forum::class)->findOneBy(['slug' => $entry['slug']]);
            $isNew = !$forum instanceof Forum;

            if ($isNew) {
                $forum = new Forum();
                $forum->setSlug($entry['slug']);
                ++$stats['created'];
            } else {
                ++$stats['updated'];
            }

            $forum->setName($entry['name']);
            $forum->setCategory($category);
            $forum->setUniverse($universe);
            $forum->setParent($parent);
            $forum->setType($entry['type']);
            $forum->setIsRoleplay($entry['is_roleplay']);
            $forum->setPosition($entry['position']);
            $forum->setStatus('open');

            if (!$dryRun) {
                $this->entityManager->persist($forum);
            }

            $this->forumBySourceSlug[$entry['source_slug']] = $forum;

            $io->writeln(sprintf(
                '  [%s] %s → %s%s',
                $isNew ? 'CREATE' : 'UPDATE',
                $entry['source_slug'],
                $entry['slug'],
                $parent instanceof Forum ? sprintf(' (parent: %s)', $parent->getSlug()) : '',
            ));
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $stats['categories'] = count(array_unique(array_column($entries, 'category_type')));

        return $stats;
    }

    /**
     * @return list<array{
     *     source_slug: string,
     *     slug: string,
     *     name: string,
     *     parent_source_slug: string|null,
     *     category_type: string,
     *     category_slug: string,
     *     type: string,
     *     is_roleplay: bool,
     *     position: int
     * }>
     */
    private function collectEntries(array $payload): array
    {
        $entries = [];
        $skipCategories = $this->config['skip_categories'] ?? [];
        $skipForumSlugs = $this->config['skip_forum_slugs'] ?? [];

        /** @var list<array<string, mixed>> $categories */
        $categories = $payload['categories'] ?? [];

        foreach ($categories as $categoryBlock) {
            $categoryType = (string) ($categoryBlock['type'] ?? '');
            if (in_array($categoryType, $skipCategories, true)) {
                continue;
            }

            $categoryDefaults = $this->config['categories'][$categoryType] ?? null;
            if (!is_array($categoryDefaults)) {
                throw new \RuntimeException(sprintf("Type de catégorie '%s' non mappé dans forum-import.json.", $categoryType));
            }

            /** @var list<array<string, mixed>> $forums */
            $forums = $categoryBlock['forums'] ?? [];
            foreach ($forums as $position => $forumData) {
                $this->collectForumTree(
                    $entries,
                    $forumData,
                    $categoryType,
                    $categoryDefaults,
                    null,
                    (int) $position + 1,
                    $skipForumSlugs,
                );
            }
        }

        return $entries;
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array<string, mixed> $forumData
     * @param array<string, mixed> $categoryDefaults
     * @param list<string> $skipForumSlugs
     */
    private function collectForumTree(
        array &$entries,
        array $forumData,
        string $categoryType,
        array $categoryDefaults,
        ?string $parentSourceSlug,
        int $position,
        array $skipForumSlugs,
    ): void {
        $sourceSlug = (string) ($forumData['slug'] ?? '');
        if ($sourceSlug === '' || in_array($sourceSlug, $skipForumSlugs, true)) {
            return;
        }

        $forumOverride = $this->config['forum_overrides'][$sourceSlug] ?? [];
        if (!is_array($forumOverride)) {
            $forumOverride = [];
        }

        $parentDefaults = $parentSourceSlug !== null
            ? $this->findEntryDefaults($entries, $parentSourceSlug)
            : null;

        $type = (string) ($forumOverride['type'] ?? $parentDefaults['type'] ?? $categoryDefaults['default_type']);
        $isRoleplay = (bool) ($forumOverride['is_roleplay'] ?? $parentDefaults['is_roleplay'] ?? $categoryDefaults['default_is_roleplay']);
        $categorySlug = (string) ($forumOverride['category_slug'] ?? $parentDefaults['category_slug'] ?? $categoryDefaults['category_slug']);

        $entries[] = [
            'source_slug' => $sourceSlug,
            'slug' => $this->resolveSlug($sourceSlug),
            'name' => (string) ($forumData['titre'] ?? $sourceSlug),
            'parent_source_slug' => $parentSourceSlug,
            'category_type' => $categoryType,
            'category_slug' => $categorySlug,
            'type' => $type,
            'is_roleplay' => $isRoleplay,
            'position' => $position,
        ];

        /** @var list<array<string, mixed>> $subForums */
        $subForums = $forumData['sous_forums'] ?? [];
        foreach ($subForums as $subPosition => $subForumData) {
            $this->collectForumTree(
                $entries,
                $subForumData,
                $categoryType,
                $categoryDefaults,
                $sourceSlug,
                (int) $subPosition + 1,
                $skipForumSlugs,
            );
        }
    }

    private function resolveSlug(string $sourceSlug): string
    {
        /** @var array<string, string> $overrides */
        $overrides = $this->config['slug_overrides'] ?? [];
        if (isset($overrides[$sourceSlug])) {
            return $overrides[$sourceSlug];
        }

        if (preg_match('/^f\d+-(.+)$/', $sourceSlug, $matches)) {
            return $matches[1];
        }

        return $sourceSlug;
    }

    private function resolveUniverse(): Univers
    {
        $slug = (string) ($this->config['universe_slug'] ?? 'dc');
        $universe = $this->entityManager->getRepository(Univers::class)->findOneBy(['slug' => $slug]);
        if (!$universe instanceof Univers) {
            throw new \RuntimeException(sprintf("Univers '%s' introuvable.", $slug));
        }

        return $universe;
    }

    /**
     * @return array<string, ForumCategory>
     */
    private function resolveCategories(): array
    {
        $requiredSlugs = [];
        foreach ($this->config['categories'] ?? [] as $categoryConfig) {
            if (is_array($categoryConfig) && isset($categoryConfig['category_slug'])) {
                $requiredSlugs[] = (string) $categoryConfig['category_slug'];
            }
        }
        foreach ($this->config['forum_overrides'] ?? [] as $override) {
            if (is_array($override) && isset($override['category_slug'])) {
                $requiredSlugs[] = (string) $override['category_slug'];
            }
        }

        $requiredSlugs = array_unique($requiredSlugs);
        $resolved = [];

        foreach ($requiredSlugs as $slug) {
            $category = $this->entityManager->getRepository(ForumCategory::class)->findOneBy(['slug' => $slug]);
            if (!$category instanceof ForumCategory) {
                throw new \RuntimeException(sprintf("Catégorie '%s' introuvable. Chargez ForumCategoryFixtures.", $slug));
            }
            $resolved[$slug] = $category;
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        if (!is_readable($this->configFile)) {
            throw new \RuntimeException(sprintf('Fichier de mapping introuvable : %s', $this->configFile));
        }

        $config = json_decode((string) file_get_contents($this->configFile), true);
        if (!is_array($config)) {
            throw new \RuntimeException(sprintf('JSON invalide : %s', $this->configFile));
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadSourceFile(string $sourceFile): array
    {
        if (!is_readable($sourceFile)) {
            throw new \RuntimeException(sprintf('Fichier source introuvable : %s', $sourceFile));
        }

        $payload = json_decode((string) file_get_contents($sourceFile), true);
        if (!is_array($payload)) {
            throw new \RuntimeException(sprintf('JSON invalide : %s', $sourceFile));
        }

        return $payload;
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return array{type: string, is_roleplay: bool, category_slug: string}|null
     */
    private function findEntryDefaults(array $entries, string $parentSourceSlug): ?array
    {
        for ($i = count($entries) - 1; $i >= 0; --$i) {
            if (($entries[$i]['source_slug'] ?? '') === $parentSourceSlug) {
                return [
                    'type' => (string) $entries[$i]['type'],
                    'is_roleplay' => (bool) $entries[$i]['is_roleplay'],
                    'category_slug' => (string) $entries[$i]['category_slug'],
                ];
            }
        }

        return null;
    }
}
