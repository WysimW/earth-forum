<?php

namespace App\Service\ForumActif;

final class ForumActifState
{
    /** @param array<string, mixed> $data */
    public function __construct(
        private array $data,
        private readonly string $path,
    ) {
    }

    public static function load(string $path): self
    {
        if (!is_file($path)) {
            return new self([
                'threads' => [],
                'posts' => [],
                'exportedThreads' => [],
            ], $path);
        }

        $json = file_get_contents($path);
        $data = json_decode($json ?: '{}', true);

        return new self(is_array($data) ? $data : [], $path);
    }

    public function save(): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Impossible de créer le dossier %s', $dir));
        }

        file_put_contents(
            $this->path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
    }

    public function isThreadExported(int $sourceThreadId): bool
    {
        return isset($this->data['exportedThreads'][(string) $sourceThreadId]);
    }

    public function markThreadExported(int $sourceThreadId, int $year): void
    {
        $this->data['exportedThreads'][(string) $sourceThreadId] = [
            'year' => $year,
            'at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];
    }

    public function getLocalThreadId(int $sourceThreadId): ?int
    {
        $value = $this->data['threads'][(string) $sourceThreadId] ?? null;

        return is_int($value) ? $value : (is_array($value) ? ($value['localId'] ?? null) : null);
    }

    public function setLocalThreadId(int $sourceThreadId, int $localThreadId): void
    {
        $this->data['threads'][(string) $sourceThreadId] = $localThreadId;
    }

    public function isPostImported(int $sourcePostId): bool
    {
        return isset($this->data['posts'][(string) $sourcePostId]);
    }

    public function setLocalPostId(int $sourcePostId, int $localPostId): void
    {
        $this->data['posts'][(string) $sourcePostId] = $localPostId;
    }
}
