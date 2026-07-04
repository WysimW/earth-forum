<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Régénère les URLs pré-signées S3 expirées stockées en base (avatars, bannières, etc.).
 */
final class S3MediaUrlResolver
{
    private string $bucket;

    public function __construct(
        private readonly S3Service $s3Service,
        private readonly LoggerInterface $logger,
    ) {
        $this->bucket = trim((string) ($_ENV['AWS_S3_BUCKET'] ?? ''));
    }

    public function resolve(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        if ($this->bucket === '') {
            return $url;
        }

        if (!$this->looksLikeAwsS3HttpUrl($url)) {
            return $url;
        }

        if (!$this->looksLikeSignedAwsHttpUrl($url)) {
            return $url;
        }

        if (!$this->isPresignedUrlExpired($url)) {
            return $url;
        }

        $key = $this->extractObjectKey($url);
        if ($key === null) {
            return $url;
        }

        try {
            return $this->s3Service->getPresignedUrl($key, 604800);
        } catch (\Throwable $e) {
            $this->logger->warning('S3MediaUrlResolver: échec régénération URL', [
                'message' => $e->getMessage(),
            ]);

            return $url;
        }
    }

    private function looksLikeAwsS3HttpUrl(string $url): bool
    {
        return str_contains($url, 'amazonaws.com')
            || str_contains($url, 'amazonaws.cn')
            || str_contains($url, 's3.amazonaws.com');
    }

    /** SigV4 (X-Amz-Signature) ou SigV2 historique (Signature en query string). */
    private function looksLikeSignedAwsHttpUrl(string $url): bool
    {
        return stripos($url, 'X-Amz-Signature') !== false || preg_match('/[&?]Signature=/i', $url) === 1;
    }

    /**
     * SigV4 (X-Amz-Date + X-Amz-Expires) ou SigV2 (Expires timestamp unix).
     *
     * @see Media::isUrlExpired()
     */
    private function isPresignedUrlExpired(string $url): bool
    {
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['query'])) {
            return false;
        }

        parse_str($parsedUrl['query'], $params);

        // Anciennes URLs SigV2 (AWSAccessKeyId + Expires unix + Signature).
        if (isset($params['Expires'], $params['Signature']) && ctype_digit((string) $params['Expires'])) {
            return time() > (int) $params['Expires'];
        }

        $dateStr = $params['X-Amz-Date'] ?? ($params['x-amz-date'] ?? null);
        $expiresRaw = $params['X-Amz-Expires'] ?? ($params['x-amz-expires'] ?? null);
        if (!$dateStr || $expiresRaw === null || $expiresRaw === '') {
            return false;
        }

        $expires = (int) $expiresRaw;

        try {
            $dateTime = \DateTimeImmutable::createFromFormat('Ymd\THis\Z', $dateStr);
            if (!$dateTime) {
                return true;
            }
            $expirationDate = $dateTime->modify("+{$expires} seconds");

            return new \DateTimeImmutable() > $expirationDate;
        } catch (\Exception) {
            return true;
        }
    }

    private function extractObjectKey(string $url): ?string
    {
        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['host'])) {
            return null;
        }

        $host = $parsed['host'];
        $path = $parsed['path'] ?? '';

        // Virtual-hosted–style : {bucket}.s3…amazonaws.com/{key}
        if (preg_match('/^(.+)\.s3(?:[.-][a-z0-9-]+)?\.amazonaws\.com$/', $host, $m)) {
            if (!$this->bucketMatches((string) $m[1])) {
                return null;
            }
            $key = ltrim(rawurldecode($path), '/');

            return $key !== '' ? $key : null;
        }

        // Virtual-hosted dual-stack : {bucket}.s3.dualstack.{region}.amazonaws.com/{key}
        if (preg_match('/^(.+)\.s3\.dualstack\.[a-z0-9-]+\.amazonaws\.com$/', $host, $m)) {
            if (!$this->bucketMatches((string) $m[1])) {
                return null;
            }
            $key = ltrim(rawurldecode($path), '/');

            return $key !== '' ? $key : null;
        }

        // Partition AWS Chine (*.amazonaws.com.cn)
        if (preg_match('/^(.+)\.s3(?:\.[a-z0-9-]+)?\.amazonaws\.com\.cn$/', $host, $m)) {
            if (!$this->bucketMatches((string) $m[1])) {
                return null;
            }
            $key = ltrim(rawurldecode($path), '/');

            return $key !== '' ? $key : null;
        }

        // Path-style : s3.{region}.amazonaws.com/{bucket}/{key}
        if (preg_match('/^s3[.-][a-z0-9-]+\.amazonaws\.com$/', $host)) {
            $trimmed = ltrim(rawurldecode($path), '/');
            $pos = strpos($trimmed, '/');
            if ($pos === false) {
                return null;
            }
            $bucketInPath = substr($trimmed, 0, $pos);
            $key = substr($trimmed, $pos + 1);
            if (!$this->bucketMatches($bucketInPath) || $key === '') {
                return null;
            }

            return $key;
        }

        return null;
    }

    private function bucketMatches(string $bucketFromUrl): bool
    {
        if ($this->bucket === '') {
            return false;
        }

        return strcasecmp(trim($bucketFromUrl), $this->bucket) === 0;
    }
}
