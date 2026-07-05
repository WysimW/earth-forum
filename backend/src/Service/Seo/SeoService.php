<?php

namespace App\Service\Seo;

use App\Entity\Embeddable\SeoMetadata;
use App\Repository\SiteSettingRepository;
use App\Service\S3MediaUrlResolver;

class SeoService
{
    public const SITE_SUFFIX = 'Earth Forum';
    public const TITLE_MAX = 70;
    public const DESCRIPTION_MAX = 160;

    private const SITE_PAGE_KEYS = ['portal', 'about', 'regulation', 'guide'];

    public function __construct(
        private readonly SiteSettingRepository $siteSettingRepository,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
        private readonly string $frontendBaseUrl = 'http://localhost:3003',
    ) {
    }

    public function getFrontendBaseUrl(): string
    {
        return rtrim($this->frontendBaseUrl, '/');
    }

    public function buildAbsoluteUrl(string $path): string
    {
        if ($path === '' || $path === '/') {
            return $this->getFrontendBaseUrl();
        }

        return $this->getFrontendBaseUrl() . (str_starts_with($path, '/') ? $path : '/' . $path);
    }

    /**
     * @return array{metaTitle: string, metaDescription: string, ogImage: ?string, robotsIndex: bool, canonical: string}
     */
    public function resolveFromMetadata(
        SeoMetadata $metadata,
        ?string $fallbackTitle,
        ?string $fallbackDescription,
        ?string $fallbackOgImage,
        string $canonicalPath,
    ): array {
        $metaTitle = $this->truncate(
            $this->firstNonEmpty($metadata->getMetaTitle(), $fallbackTitle, self::SITE_SUFFIX),
            self::TITLE_MAX
        );

        if ($metadata->getMetaTitle() === null && $fallbackTitle !== null && $fallbackTitle !== self::SITE_SUFFIX) {
            $metaTitle = $this->truncate($this->appendSiteSuffix($fallbackTitle), self::TITLE_MAX);
        }

        $metaDescription = $this->truncate(
            $this->firstNonEmpty(
                $metadata->getMetaDescription(),
                $fallbackDescription,
                'Forum de roleplay ' . self::SITE_SUFFIX
            ),
            self::DESCRIPTION_MAX
        );

        $ogImage = $this->s3MediaUrlResolver->resolve(
            $this->firstNonEmpty($metadata->getOgImage(), $fallbackOgImage)
        );

        return [
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'ogImage' => $ogImage,
            'robotsIndex' => $metadata->isRobotsIndex(),
            'canonical' => $this->buildAbsoluteUrl($canonicalPath),
        ];
    }

    /**
     * @return array{metaTitle: ?string, metaDescription: ?string, ogImage: ?string, robotsIndex: bool}|null
     */
    public function getSitePageSeoRaw(string $pageKey): ?array
    {
        if (!in_array($pageKey, self::SITE_PAGE_KEYS, true)) {
            return null;
        }

        $json = $this->siteSettingRepository->getValue('seo.' . $pageKey);
        if ($json === null || $json === '') {
            return null;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }

        return $this->normalizeSeoInput($data);
    }

    /**
     * @return array{metaTitle: string, metaDescription: string, ogImage: ?string, robotsIndex: bool, canonical: string}
     */
    public function resolveSitePage(
        string $pageKey,
        ?string $fallbackTitle,
        ?string $fallbackDescription,
        string $canonicalPath,
        ?string $fallbackOgImage = null,
    ): array {
        $raw = $this->getSitePageSeoRaw($pageKey);
        $metadata = $this->metadataFromArray($raw ?? []);

        return $this->resolveFromMetadata(
            $metadata,
            $fallbackTitle,
            $fallbackDescription,
            $fallbackOgImage,
            $canonicalPath
        );
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function applySeoInput(SeoMetadata $metadata, ?array $data): void
    {
        if ($data === null) {
            return;
        }

        $normalized = $this->normalizeSeoInput($data);
        if ($normalized === null) {
            return;
        }

        if (array_key_exists('metaTitle', $normalized)) {
            $metadata->setMetaTitle($normalized['metaTitle']);
        }
        if (array_key_exists('metaDescription', $normalized)) {
            $metadata->setMetaDescription($normalized['metaDescription']);
        }
        if (array_key_exists('ogImage', $normalized)) {
            $metadata->setOgImage($normalized['ogImage']);
        }
        if (array_key_exists('robotsIndex', $normalized)) {
            $metadata->setRobotsIndex((bool) $normalized['robotsIndex']);
        }
    }

    public function saveSitePageSeo(string $pageKey, array $data): void
    {
        if (!in_array($pageKey, self::SITE_PAGE_KEYS, true)) {
            throw new \InvalidArgumentException('Page SEO inconnue: ' . $pageKey);
        }

        $normalized = $this->normalizeSeoInput($data);
        $setting = $this->siteSettingRepository->findOneBy(['settingKey' => 'seo.' . $pageKey])
            ?? (new \App\Entity\SiteSetting())->setSettingKey('seo.' . $pageKey);
        $setting->setSettingValue(json_encode($normalized ?? [], JSON_UNESCAPED_UNICODE));
        $em = $this->siteSettingRepository->getEntityManager();
        $em->persist($setting);
        $em->flush();
    }

    /**
     * @return array{metaTitle: ?string, metaDescription: ?string, ogImage: ?string, robotsIndex: bool}
     */
    public function serializeMetadata(SeoMetadata $metadata): array
    {
        return [
            'metaTitle' => $metadata->getMetaTitle(),
            'metaDescription' => $metadata->getMetaDescription(),
            'ogImage' => $this->s3MediaUrlResolver->resolve($metadata->getOgImage()),
            'robotsIndex' => $metadata->isRobotsIndex(),
        ];
    }

    /**
     * @return array{metaTitle: ?string, metaDescription: ?string, ogImage: ?string, robotsIndex: bool}|null
     */
    public function normalizeSeoInput(array $data): ?array
    {
        $result = [];

        if (array_key_exists('metaTitle', $data)) {
            $title = trim((string) $data['metaTitle']);
            $result['metaTitle'] = $title !== '' ? $this->truncate($title, self::TITLE_MAX) : null;
        }
        if (array_key_exists('metaDescription', $data)) {
            $description = trim((string) $data['metaDescription']);
            $result['metaDescription'] = $description !== '' ? $this->truncate($description, self::DESCRIPTION_MAX) : null;
        }
        if (array_key_exists('ogImage', $data)) {
            $image = trim((string) $data['ogImage']);
            $result['ogImage'] = $image !== '' ? $image : null;
        }
        if (array_key_exists('robotsIndex', $data)) {
            $result['robotsIndex'] = (bool) $data['robotsIndex'];
        }

        return $result === [] ? null : $result;
    }

    public function stripHtml(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    public function truncate(?string $value, int $max): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $max - 1)) . '…';
    }

    private function appendSiteSuffix(string $title): string
    {
        if (str_contains($title, self::SITE_SUFFIX)) {
            return $title;
        }

        return $title . ' | ' . self::SITE_SUFFIX;
    }

    private function firstNonEmpty(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function metadataFromArray(array $data): SeoMetadata
    {
        $metadata = new SeoMetadata();
        $this->applySeoInput($metadata, $data);

        return $metadata;
    }
}
