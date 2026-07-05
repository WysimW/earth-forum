<?php

namespace App\Service\ForumActif;

final class ForumActifClient
{
    private float $lastRequestAt = 0.0;

    public function __construct(
        private readonly string $baseUrl = 'https://dc-earth.fra.co',
        private readonly int $delayMs = 1000,
        private readonly string $userAgent = 'EarthForum-Importer/1.0',
    ) {
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    public function fetch(string $pathOrUrl): string
    {
        $url = str_starts_with($pathOrUrl, 'http')
            ? $pathOrUrl
            : $this->getBaseUrl() . '/' . ltrim($pathOrUrl, '/');

        $this->throttle();

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Impossible d\'initialiser cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => ['Accept-Language: fr-FR,fr;q=0.9'],
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException(sprintf('Erreur HTTP pour %s : %s', $url, $error));
        }

        if ($status >= 400) {
            throw new \RuntimeException(sprintf('HTTP %d pour %s', $status, $url));
        }

        return $body;
    }

    private function throttle(): void
    {
        if ($this->delayMs <= 0) {
            return;
        }

        $now = microtime(true);
        $elapsedMs = ($now - $this->lastRequestAt) * 1000;
        if ($this->lastRequestAt > 0 && $elapsedMs < $this->delayMs) {
            usleep((int) (($this->delayMs - $elapsedMs) * 1000));
        }

        $this->lastRequestAt = microtime(true);
    }
}
