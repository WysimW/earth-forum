<?php

namespace App\Controller\Api;

use App\Entity\Univers;
use App\Repository\ForumRepository;
use App\Repository\SiteSettingRepository;
use App\Repository\UniversRepository;
use App\Service\Seo\SeoService;
use App\Service\Seo\ThreadSeoResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SeoController extends AbstractController
{
    private const SETTING_ABOUT_CONTENT = 'about_content';

    public function __construct(
        private readonly SeoService $seoService,
        private readonly ThreadSeoResolver $threadSeoResolver,
        private readonly UniversRepository $universRepository,
        private readonly ForumRepository $forumRepository,
        private readonly SiteSettingRepository $siteSettingRepository,
    ) {
    }

    #[Route('/api/seo/portal', name: 'api_seo_portal', methods: ['GET'])]
    public function portal(): JsonResponse
    {
        $seo = $this->seoService->resolveSitePage(
            'portal',
            'Earth Forum — Forum de roleplay',
            'Explorez les univers DC, Marvel, Star Wars et plus encore. Rejoignez la communauté Earth Forum.',
            '/'
        );

        return new JsonResponse(['seo' => $seo]);
    }

    #[Route('/api/seo/about', name: 'api_seo_about', methods: ['GET'])]
    public function about(): JsonResponse
    {
        $content = $this->siteSettingRepository->getValue(self::SETTING_ABOUT_CONTENT, '');
        $seo = $this->seoService->resolveSitePage(
            'about',
            'Qui sommes-nous',
            'Découvrez l\'histoire et la communauté d\'Earth Forum.',
            '/qui-sommes-nous'
        );

        return new JsonResponse([
            'content' => $content,
            'seo' => $seo,
        ]);
    }

    #[Route('/api/seo/universes/{slug}', name: 'api_seo_universe', methods: ['GET'])]
    public function universe(string $slug): JsonResponse
    {
        $universe = $this->universRepository->findOneBy(['slug' => $slug]);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'universe' => [
                'id' => $universe->getId(),
                'name' => $universe->getName(),
                'slug' => $universe->getSlug(),
            ],
            'seo' => $this->resolveUniverseSeo($universe, 'page'),
            'memberOfMonthSeo' => $this->resolveUniverseSeo($universe, 'member_of_month'),
            'characterOfMonthSeo' => $this->resolveUniverseSeo($universe, 'character_of_month'),
        ]);
    }

    #[Route('/api/seo/forums/{slug}', name: 'api_seo_forum', methods: ['GET'])]
    public function forum(string $slug): JsonResponse
    {
        $forum = is_numeric($slug)
            ? $this->forumRepository->find((int) $slug)
            : $this->forumRepository->findOneBy(['slug' => $slug]);

        if (!$forum || $forum->getStatus() === 'archived') {
            return new JsonResponse(['error' => 'Forum introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $canonicalPath = '/forums/' . ($forum->getSlug() ?? (string) $forum->getId());
        $seo = $this->seoService->resolveFromMetadata(
            $forum->getSeo(),
            $this->seoService->truncate($forum->getName() . ' | Earth Forum', SeoService::TITLE_MAX),
            $forum->getDescription(),
            $forum->getBanner() ?? $forum->getHeroLogo(),
            $canonicalPath
        );

        return new JsonResponse(['seo' => $seo]);
    }

    #[Route('/api/seo/threads/{slug}', name: 'api_seo_thread', methods: ['GET'])]
    public function thread(string $slug): JsonResponse
    {
        $seo = $this->threadSeoResolver->resolveBySlug($slug);
        if ($seo === null) {
            return new JsonResponse(['error' => 'Thread introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['seo' => $seo]);
    }

    /**
     * @return array{metaTitle: string, metaDescription: string, ogImage: ?string, robotsIndex: bool, canonical: string}
     */
    private function resolveUniverseSeo(Univers $universe, string $context): array
    {
        $slug = $universe->getSlug() ?? '';
        $name = $universe->getName() ?? 'Univers';

        return match ($context) {
            'member_of_month' => $this->seoService->resolveFromMetadata(
                $universe->getMemberOfMonthSeo(),
                'Membre du mois — ' . $name,
                'Découvrez le membre du mois de l\'univers ' . $name . '.',
                null,
                '/member-of-month/' . $slug
            ),
            'character_of_month' => $this->seoService->resolveFromMetadata(
                $universe->getCharacterOfMonthSeo(),
                'Personnage du mois — ' . $name,
                'Découvrez le personnage du mois de l\'univers ' . $name . '.',
                null,
                '/character-of-month/' . $slug
            ),
            default => $this->seoService->resolveFromMetadata(
                $universe->getSeo(),
                'Forums ' . $name . ' | Earth Forum',
                $universe->getDescription(),
                null,
                '/univers/' . $slug
            ),
        };
    }
}
