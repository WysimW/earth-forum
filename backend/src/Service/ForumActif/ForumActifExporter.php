<?php

namespace App\Service\ForumActif;

use Symfony\Component\Console\Style\SymfonyStyle;

final class ForumActifExporter
{
    public function __construct(
        private readonly ForumActifClient $client,
        private readonly ForumActifParser $parser,
    ) {
    }

    /**
     * @return array{threads: int, posts: int, skippedThreads: int}
     */
    public function exportYear(
        int $year,
        string $outputDir,
        ForumActifState $state,
        SymfonyStyle $io,
        bool $resume = false,
        ?int $forumSourceId = null,
    ): array {
        $yearDir = rtrim($outputDir, '/') . '/' . $year;
        $threadsDir = $yearDir . '/threads';
        if (!is_dir($threadsDir) && !mkdir($threadsDir, 0775, true) && !is_dir($threadsDir)) {
            throw new \RuntimeException(sprintf('Impossible de créer %s', $threadsDir));
        }

        $reference = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $discoveredThreads = [];
        $forums = $this->discoverForums();

        if ($forumSourceId !== null) {
            $forums = array_values(array_filter($forums, static fn (array $forum): bool => $forum['id'] === $forumSourceId));
            if ($forums === []) {
                throw new \RuntimeException(sprintf('Forum source #%d introuvable.', $forumSourceId));
            }
        }

        $io->section(sprintf('Découverte des sujets (%d forums)', count($forums)));
        foreach ($forums as $forum) {
            $io->writeln(sprintf('  Forum %s (%s)', $forum['name'], $forum['path']));
            foreach ($this->discoverThreadsInForum($forum['path']) as $thread) {
                $discoveredThreads[$thread['id']] = $thread + ['forum' => $forum];
            }
        }

        $io->writeln(sprintf('<info>%d sujets uniques trouvés</info>', count($discoveredThreads)));

        $stats = ['threads' => 0, 'posts' => 0, 'skippedThreads' => 0];
        $io->progressStart(count($discoveredThreads));

        foreach ($discoveredThreads as $thread) {
            $io->progressAdvance();

            if ($resume && $state->isThreadExported($thread['id'])) {
                ++$stats['skippedThreads'];
                continue;
            }

            $export = $this->exportThread($thread, $year, $reference);
            if ($export === null) {
                continue;
            }

            $file = sprintf('%s/t%d.json', $threadsDir, $thread['id']);
            file_put_contents(
                $file,
                json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );

            $state->markThreadExported($thread['id'], $year);
            $state->save();

            ++$stats['threads'];
            $stats['posts'] += count($export['posts']);
        }

        $io->progressFinish();

        file_put_contents(
            $yearDir . '/manifest.json',
            json_encode([
                'year' => $year,
                'exportedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'source' => $this->client->getBaseUrl(),
                'stats' => $stats,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        return $stats;
    }

    /**
     * @return list<array{id: int, slug: string, name: string, path: string}>
     */
    private function discoverForums(): array
    {
        $html = $this->client->fetch('/');
        $forums = $this->parser->extractForums($html);

        foreach (array_keys($forums) as $index) {
            $forums[$index]['name'] = trim($forums[$index]['name']);
        }

        usort($forums, static fn (array $a, array $b): int => $a['id'] <=> $b['id']);

        return $forums;
    }

    /**
     * @return list<array{id: int, slug: string, title: string, path: string}>
     */
    private function discoverThreadsInForum(string $forumPath): array
    {
        $html = $this->client->fetch($forumPath);
        $threads = [];

        foreach ($this->parser->extractForumPagePaths($html, $forumPath) as $pagePath) {
            $pageHtml = $pagePath === $forumPath ? $html : $this->client->fetch($pagePath);
            foreach ($this->parser->extractThreads($pageHtml) as $thread) {
                $threads[$thread['id']] = $thread;
            }
        }

        return array_values($threads);
    }

    /**
     * @param array<string, mixed> $thread
     *
     * @return array<string, mixed>|null
     */
    private function exportThread(array $thread, int $year, \DateTimeImmutable $reference): ?array
    {
        $posts = [];
        $title = $thread['title'];
        $forum = $thread['forum'] ?? null;

        $firstPageHtml = $this->client->fetch($thread['path']);
        $pagePaths = $this->parser->extractThreadPagePaths($firstPageHtml, $thread['path']);

        foreach ($pagePaths as $pageIndex => $pagePath) {
            $pageHtml = $pageIndex === 0 ? $firstPageHtml : $this->client->fetch($pagePath);

            if ($pageIndex === 0) {
                $title = $this->parser->extractThreadTitleFromPage($pageHtml) ?? $title;
                $breadcrumbForum = $this->parser->extractForumFromThreadBreadcrumb($pageHtml);
                if ($breadcrumbForum !== null) {
                    $forum = $breadcrumbForum;
                }
            }

            foreach ($this->parser->extractPosts($pageHtml, $reference) as $post) {
                if ($this->postMatchesYear($post, $year, $reference)) {
                    $posts[$post['sourceId']] = $post;
                }
            }
        }

        if ($posts === []) {
            return null;
        }

        ksort($posts);

        return [
            'source' => [
                'threadId' => $thread['id'],
                'threadSlug' => $thread['slug'],
                'url' => $this->client->getBaseUrl() . $thread['path'],
            ],
            'forum' => $forum,
            'title' => $title,
            'year' => $year,
            'posts' => array_values($posts),
        ];
    }

    /**
     * @param array{postedAt: ?string, postedAtRaw: string} $post
     */
    private function postMatchesYear(array $post, int $year, \DateTimeImmutable $reference): bool
    {
        if ($post['postedAt'] !== null) {
            return (int) (new \DateTimeImmutable($post['postedAt']))->format('Y') === $year;
        }

        if (preg_match('/(\d{4})/', $post['postedAtRaw'], $match)) {
            return (int) $match[1] === $year;
        }

        if (preg_match("/Aujourd'hui|Hier/ui", $post['postedAtRaw'])) {
            return (int) $reference->format('Y') === $year;
        }

        return false;
    }
}
