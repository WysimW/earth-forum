<?php

namespace App\Controller\Api;

use App\Entity\Univers;
use App\Repository\SiteSettingRepository;
use App\Repository\UniversRepository;
use App\Service\Seo\SeoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminSeoController extends AbstractController
{
    private const SETTING_ABOUT_CONTENT = 'about_content';
    private const ALLOWED_SITE_PAGES = ['portal', 'about', 'regulation', 'guide'];

    public function __construct(
        private readonly SeoService $seoService,
        private readonly UniversRepository $universRepository,
        private readonly SiteSettingRepository $siteSettingRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/admin/seo/site-pages/{pageKey}', name: 'api_admin_seo_site_page_get', methods: ['GET'])]
    public function getSitePage(string $pageKey): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        if (!in_array($pageKey, self::ALLOWED_SITE_PAGES, true)) {
            return new JsonResponse(['error' => 'Page inconnue'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $response = [
            'pageKey' => $pageKey,
            'seo' => $this->seoService->getSitePageSeoRaw($pageKey) ?? [
                'metaTitle' => null,
                'metaDescription' => null,
                'ogImage' => null,
                'robotsIndex' => true,
            ],
        ];

        if ($pageKey === 'about') {
            $response['content'] = $this->siteSettingRepository->getValue(self::SETTING_ABOUT_CONTENT, '');
        }

        return new JsonResponse($response);
    }

    #[Route('/api/admin/seo/site-pages/{pageKey}', name: 'api_admin_seo_site_page_update', methods: ['PUT'])]
    public function updateSitePage(string $pageKey, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        if (!in_array($pageKey, self::ALLOWED_SITE_PAGES, true)) {
            return new JsonResponse(['error' => 'Page inconnue'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'JSON invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (isset($payload['seo']) && is_array($payload['seo'])) {
            $this->seoService->saveSitePageSeo($pageKey, $payload['seo']);
        }

        if ($pageKey === 'about' && array_key_exists('content', $payload)) {
            $this->upsertSetting(self::SETTING_ABOUT_CONTENT, (string) $payload['content']);
            $this->entityManager->flush();
        }

        return new JsonResponse(['status' => 'saved']);
    }

    #[Route('/api/admin/universes/{id}/seo', name: 'api_admin_universe_seo_get', methods: ['GET'])]
    public function getUniverseSeo(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $universe = $this->universRepository->find($id);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->isUniverseAllowed($universe)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'universe' => [
                'id' => $universe->getId(),
                'name' => $universe->getName(),
                'slug' => $universe->getSlug(),
            ],
            'seo' => $this->seoService->serializeMetadata($universe->getSeo()),
            'memberOfMonthSeo' => $this->seoService->serializeMetadata($universe->getMemberOfMonthSeo()),
            'characterOfMonthSeo' => $this->seoService->serializeMetadata($universe->getCharacterOfMonthSeo()),
        ]);
    }

    #[Route('/api/admin/universes/{id}/seo', name: 'api_admin_universe_seo_update', methods: ['PUT'])]
    public function updateUniverseSeo(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $universe = $this->universRepository->find($id);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->isUniverseAllowed($universe)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'JSON invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (isset($payload['seo']) && is_array($payload['seo'])) {
            $this->seoService->applySeoInput($universe->getSeo(), $payload['seo']);
        }
        if (isset($payload['memberOfMonthSeo']) && is_array($payload['memberOfMonthSeo'])) {
            $this->seoService->applySeoInput($universe->getMemberOfMonthSeo(), $payload['memberOfMonthSeo']);
        }
        if (isset($payload['characterOfMonthSeo']) && is_array($payload['characterOfMonthSeo'])) {
            $this->seoService->applySeoInput($universe->getCharacterOfMonthSeo(), $payload['characterOfMonthSeo']);
        }

        $this->entityManager->flush();

        return new JsonResponse(['status' => 'saved']);
    }

    private function upsertSetting(string $key, ?string $value): void
    {
        $setting = $this->siteSettingRepository->findOneBy(['settingKey' => $key])
            ?? (new \App\Entity\SiteSetting())->setSettingKey($key);
        $setting->setSettingValue($value);
        $this->entityManager->persist($setting);
    }

    private function isUniverseAllowed(Univers $universe): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        foreach ($user->getAdminUniverses() as $adminUniverse) {
            if ((int) $adminUniverse->getId() === (int) $universe->getId()) {
                return true;
            }
        }

        return false;
    }
}
