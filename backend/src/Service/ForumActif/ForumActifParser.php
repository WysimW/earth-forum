<?php

namespace App\Service\ForumActif;

final class ForumActifParser
{
    public function __construct(
        private readonly ForumActifDateParser $dateParser,
    ) {
    }

    /**
     * @return list<array{id: int, slug: string, name: string, path: string}>
     */
    public function extractForums(string $html): array
    {
        $forums = [];
        if (!preg_match_all('/href="(\/f(\d+)-([^"?#]+))"[^>]*>([^<]+)</u', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $path = $match[1];
            $id = (int) $match[2];
            $slug = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $name = trim(html_entity_decode(strip_tags($match[4]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($name === '' || isset($forums[$id])) {
                continue;
            }

            $forums[$id] = [
                'id' => $id,
                'slug' => $slug,
                'name' => $name,
                'path' => $path,
            ];
        }

        return array_values($forums);
    }

    /**
     * @return list<string>
     */
    public function extractForumPagePaths(string $html, string $forumPath): array
    {
        $paths = [$forumPath];
        $forumPath = rtrim($forumPath, '/');

        if (!preg_match('#^/f(\d+)-([^/]+)$#', $forumPath, $base)) {
            return $paths;
        }

        $forumId = $base[1];
        $slug = $base[2];

        if (preg_match_all('#href="(/f' . preg_quote($forumId, '#') . 'p\d+-' . preg_quote($slug, '#') . ')"#', $html, $pages)) {
            foreach (array_unique($pages[1]) as $pagePath) {
                $paths[] = $pagePath;
            }
        }

        if (preg_match_all('#href="(/f' . preg_quote($forumId, '#') . '-' . preg_quote($slug, '#') . '\?start=\d+)"#', $html, $pages)) {
            foreach (array_unique($pages[1]) as $pagePath) {
                $paths[] = $pagePath;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return list<array{id: int, slug: string, title: string, path: string}>
     */
    public function extractThreads(string $html): array
    {
        $threads = [];

        if (preg_match_all('/href="(\/t(\d+)(?:p\d+)?-([^"?#]+))"/u', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullPath = $match[1];
                if (str_contains($fullPath, 'view=')) {
                    continue;
                }

                $canonicalPath = preg_replace('#/t(\d+)p\d+-#', '/t$1-', $fullPath);
                if (!is_string($canonicalPath)) {
                    continue;
                }

                $id = (int) $match[2];
                if (isset($threads[$id])) {
                    continue;
                }

                $slug = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $title = $this->findThreadTitle($html, $id, $slug) ?? $this->slugToTitle($slug);

                $threads[$id] = [
                    'id' => $id,
                    'slug' => $slug,
                    'title' => $title,
                    'path' => $canonicalPath,
                ];
            }
        }

        return array_values($threads);
    }

    /**
     * @return list<string>
     */
    public function extractThreadPagePaths(string $html, string $threadPath): array
    {
        $paths = [$threadPath];
        $threadPath = rtrim($threadPath, '/');

        if (!preg_match('#^/t(\d+)-([^/]+)$#', $threadPath, $base)) {
            return $paths;
        }

        $threadId = $base[1];
        $slug = $base[2];

        if (preg_match_all('#href="(/t' . preg_quote($threadId, '#') . 'p\d+-' . preg_quote($slug, '#') . ')"#', $html, $pages)) {
            foreach (array_unique($pages[1]) as $pagePath) {
                $paths[] = $pagePath;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return list<array{
     *     sourceId: int,
     *     author: string,
     *     postedAtRaw: string,
     *     postedAt: ?string,
     *     editedAtRaw: ?string,
     *     editedAt: ?string,
     *     contentHtml: string
     * }>
     */
    public function extractPosts(string $html, ?\DateTimeImmutable $reference = null): array
    {
        $posts = [];
        $chunks = preg_split('/(?=<(?:div|tr) class="post post--\d+")/', $html) ?: [];

        foreach ($chunks as $chunk) {
            if (!preg_match('/<(?:div|tr) class="post post--(\d+)" id="p\d+"/', $chunk, $idMatch)) {
                continue;
            }

            $sourceId = (int) $idMatch[1];
            if ($sourceId <= 0) {
                continue;
            }

            $author = null;
            if (preg_match('/<span class="group-\d+[^"]*"[^>]*>\s*<strong>([^<]+)<\/strong>/', $chunk, $authorMatch)) {
                $author = trim(html_entity_decode($authorMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            $postedAtRaw = null;
            if (preg_match('/<i class="ion-clock"><\/i>\s*([^<]+)/', $chunk, $dateMatch)) {
                $postedAtRaw = trim(html_entity_decode($dateMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            $editedAtRaw = null;
            if (preg_match('/Dernière édition par .+? le ([^,<]+)/u', $chunk, $editMatch)) {
                $editedAtRaw = trim(html_entity_decode($editMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            $contentHtml = '';
            if (preg_match('/<div class="postbody[^"]*">(.*?)<div class="post-bottom"/s', $chunk, $bodyMatch)) {
                $contentHtml = trim($bodyMatch[1]);
            } elseif (preg_match('/<div class="postbody[^"]*">(.*?)<\/div>\s*<div class="post-options"/s', $chunk, $bodyMatch)) {
                $contentHtml = trim($bodyMatch[1]);
            }

            if ($author === null && $contentHtml === '') {
                continue;
            }

            $postedAt = $this->dateParser->parse($postedAtRaw, $reference);
            $editedAt = $this->dateParser->parse($editedAtRaw, $reference);

            $posts[] = [
                'sourceId' => $sourceId,
                'author' => $author ?? 'Inconnu',
                'postedAtRaw' => $postedAtRaw ?? '',
                'postedAt' => $postedAt?->format(DATE_ATOM),
                'editedAtRaw' => $editedAtRaw,
                'editedAt' => $editedAt?->format(DATE_ATOM),
                'contentHtml' => $contentHtml,
            ];
        }

        return $posts;
    }

    public function extractThreadTitleFromPage(string $html): ?string
    {
        if (preg_match('/<h1[^>]*class="[^"]*topic-title[^"]*"[^>]*><a[^>]*>([^<]+)<\/a>/', $html, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<title>([^<|]+)/', $html, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return null;
    }

    public function extractForumFromThreadBreadcrumb(string $html): ?array
    {
        if (!preg_match_all('/href="(\/f(\d+)-([^"?#]+))"[^>]*>([^<]+)</u', $html, $matches, PREG_SET_ORDER)) {
            return null;
        }

        $last = end($matches);
        if ($last === false) {
            return null;
        }

        return [
            'id' => (int) $last[2],
            'slug' => html_entity_decode($last[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'name' => trim(html_entity_decode(strip_tags($last[4]), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'path' => $last[1],
        ];
    }

    private function findThreadTitle(string $html, int $threadId, string $slug): ?string
    {
        $pattern = '#href="/t' . $threadId . '(?:p\d+)?-' . preg_quote($slug, '#') . '"[^>]*>([^<]+)<#u';
        if (preg_match($pattern, $html, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return null;
    }

    private function slugToTitle(string $slug): string
    {
        $title = str_replace('-', ' ', $slug);

        return mb_convert_case($title, MB_CASE_TITLE, 'UTF-8');
    }
}
