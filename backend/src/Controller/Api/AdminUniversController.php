<?php

namespace App\Controller\Api;

use App\Entity\Univers;
use App\Repository\UniversRepository;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/admin/universes')]
class AdminUniversController extends AbstractController
{
    private const LOCKED_SLUGS = ['dc', 'marvel'];

    public function __construct(
        private readonly UniversRepository $universRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('', name: 'api_admin_universes_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $universes = $this->filterAccessibleUniverses($this->universRepository->findBy([], ['name' => 'ASC']));

        return new JsonResponse([
            'universes' => array_map(fn (Univers $univers) => $this->serializeUnivers($univers), $universes),
            'total' => count($universes),
        ]);
    }

    #[Route('/{id}', name: 'api_admin_universes_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getOne(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $univers = $this->universRepository->find($id);
        if (!$univers instanceof Univers) {
            return new JsonResponse(['error' => 'Univers introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessUnivers($univers)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->serializeUnivers($univers));
    }

    #[Route('/{id}', name: 'api_admin_universes_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $univers = $this->universRepository->find($id);
        if (!$univers instanceof Univers) {
            return new JsonResponse(['error' => 'Univers introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessUnivers($univers)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Payload JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $isSlugLocked = $this->isSlugLocked($univers->getSlug());

        if (!$isSlugLocked) {
            if (array_key_exists('name', $data) && trim((string) $data['name']) !== '') {
                $univers->setName(trim((string) $data['name']));
            }
            if (array_key_exists('slug', $data) && trim((string) $data['slug']) !== '') {
                $newSlug = strtolower((new AsciiSlugger())->slug(trim((string) $data['slug']))->toString());
                if ($newSlug === '') {
                    return new JsonResponse(['error' => 'Slug invalide'], Response::HTTP_BAD_REQUEST);
                }
                if (in_array($newSlug, self::LOCKED_SLUGS, true) && $newSlug !== $univers->getSlug()) {
                    return new JsonResponse(['error' => 'Ce slug est réservé'], Response::HTTP_BAD_REQUEST);
                }
                $existing = $this->universRepository->findOneBy(['slug' => $newSlug]);
                if ($existing instanceof Univers && $existing->getId() !== $univers->getId()) {
                    return new JsonResponse(['error' => 'Ce slug est déjà utilisé'], Response::HTTP_BAD_REQUEST);
                }
                $univers->setSlug($newSlug);
            }
        }

        if (array_key_exists('description', $data)) {
            $univers->setDescription($data['description'] !== null && $data['description'] !== ''
                ? (string) $data['description']
                : null);
        }

        if (array_key_exists('forumsTitle', $data)) {
            $univers->setForumsTitle($data['forumsTitle'] !== null && trim((string) $data['forumsTitle']) !== ''
                ? trim((string) $data['forumsTitle'])
                : null);
        }

        if (array_key_exists('portalBanner', $data)) {
            $univers->setPortalBanner($this->normalizeMediaUrl($data['portalBanner']));
        }

        if (array_key_exists('forumsHeaderBanner', $data)) {
            $univers->setForumsHeaderBanner($this->normalizeMediaUrl($data['forumsHeaderBanner']));
        }

        $this->entityManager->flush();

        return new JsonResponse($this->serializeUnivers($univers));
    }

    /**
     * @return Univers[]
     */
    private function filterAccessibleUniverses(array $universes): array
    {
        return array_values(array_filter(
            $universes,
            fn (Univers $univers) => $this->canAccessUnivers($univers)
        ));
    }

    private function canAccessUnivers(Univers $univers): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user->getAdminUniverses()->toArray()
        );

        return in_array($univers->getId(), $allowedUniverseIds, true);
    }

    private function isSlugLocked(?string $slug): bool
    {
        return $slug !== null && in_array($slug, self::LOCKED_SLUGS, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUnivers(Univers $univers): array
    {
        $slug = $univers->getSlug();

        return [
            'id' => $univers->getId(),
            'name' => $univers->getName(),
            'slug' => $slug,
            'description' => $univers->getDescription(),
            'forumsTitle' => $univers->getForumsTitle(),
            'portalBanner' => $this->s3MediaUrlResolver->resolve($univers->getPortalBanner()),
            'forumsHeaderBanner' => $this->s3MediaUrlResolver->resolve($univers->getForumsHeaderBanner()),
            'isSlugLocked' => $this->isSlugLocked($slug),
            'createdAt' => $univers->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function normalizeMediaUrl(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->s3MediaUrlResolver->normalizeStoredUrl((string) $value);
    }
}
