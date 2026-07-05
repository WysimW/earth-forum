<?php

namespace App\Service\Seo;

use App\Entity\Thread;
use App\Entity\Embeddable\SeoMetadata;
use App\Repository\PostRepository;
use App\Repository\SiteSettingRepository;
use App\Repository\ThreadRepository;

class ThreadSeoResolver
{
    public function __construct(
        private readonly ThreadRepository $threadRepository,
        private readonly PostRepository $postRepository,
        private readonly SeoService $seoService,
        private readonly SiteSettingRepository $siteSettingRepository,
    ) {
    }

    /**
     * @return array{metaTitle: string, metaDescription: string, ogImage: ?string, robotsIndex: bool, canonical: string}|null
     */
    public function resolveBySlug(string $slug): ?array
    {
        $thread = is_numeric($slug)
            ? $this->threadRepository->find((int) $slug)
            : $this->threadRepository->findOneBy(['slug' => $slug]);

        if (!$thread) {
            return null;
        }

        return $this->resolve($thread);
    }

    /**
     * @return array{metaTitle: string, metaDescription: string, ogImage: ?string, robotsIndex: bool, canonical: string}
     */
    public function resolve(Thread $thread): array
    {
        $forum = $thread->getForum();
        $forumName = $forum?->getName() ?? 'Forum';
        $fallbackTitle = $thread->getTitle() . ' | ' . $forumName;
        $fallbackDescription = $this->extractDescription($thread);
        $fallbackOgImage = $this->resolveOgImage($thread);

        $metadata = new SeoMetadata();
        $metadata->setRobotsIndex($this->shouldIndex($thread));

        $canonicalPath = '/threads/' . ($thread->getSlug() ?? (string) $thread->getId());

        return $this->seoService->resolveFromMetadata(
            $metadata,
            $fallbackTitle,
            $fallbackDescription,
            $fallbackOgImage,
            $canonicalPath
        );
    }

    private function shouldIndex(Thread $thread): bool
    {
        $status = $thread->getStatus();
        if (in_array($status, ['archived', 'private', 'closed'], true)) {
            return false;
        }

        if ($thread->isVisibleToCharactersOnly()) {
            return false;
        }

        return true;
    }

    private function extractDescription(Thread $thread): string
    {
        $content = $thread->getFirstPostContent();
        if ($content === null || trim($content) === '') {
            $firstPost = $this->postRepository->findOneBy(
                ['thread' => $thread],
                ['createdAt' => 'ASC']
            );
            $content = $firstPost?->getContent();
        }

        if ($content === null || trim($content) === '') {
            $content = $thread->getDescription();
        }

        $plain = $this->seoService->stripHtml($content);
        if ($plain === '') {
            $plain = $thread->getTitle() ?? 'Discussion sur Earth Forum';
        }

        return $this->seoService->truncate($plain, SeoService::DESCRIPTION_MAX);
    }

    private function resolveOgImage(Thread $thread): ?string
    {
        $forum = $thread->getForum();
        if ($forum?->getBanner()) {
            return $forum->getBanner();
        }
        if ($forum?->getHeroLogo()) {
            return $forum->getHeroLogo();
        }

        $author = $thread->getAuthor();
        if ($author?->getAvatar()) {
            return $author->getAvatar();
        }

        $portalSeo = $this->siteSettingRepository->getValue('seo.portal');
        if ($portalSeo) {
            $data = json_decode($portalSeo, true);
            if (is_array($data) && !empty($data['ogImage'])) {
                return (string) $data['ogImage'];
            }
        }

        return null;
    }
}
