<?php

namespace App\Controller\Api;

use App\Entity\Character;
use App\Entity\User;
use App\Repository\CharacterRepository;
use App\Repository\UniversRepository;
use App\Repository\UserRepository;
use App\Service\CharacterMergeService;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/characters')]
class AdminCharacterController extends AbstractController
{
    public function __construct(
        private CharacterRepository $characterRepository,
        private EntityManagerInterface $entityManager,
        private UniversRepository $universRepository,
        private UserRepository $userRepository,
        private CharacterMergeService $characterMergeService,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('', name: 'api_admin_characters_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->canAccessCharacterModeration($user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $search = $request->query->get('search', '');
        $statusFilter = $request->query->get('status');
        $universeFilter = $request->query->get('universe_id');
        $contextUniverseFilter = $request->query->get('context_universe_id');
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user->getAdminUniverses()->toArray()
        );

        if ($contextUniverseFilter && !$isSuperAdmin && !in_array((int) $contextUniverseFilter, $allowedUniverseIds, true)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $characters = $this->characterRepository->findAll();

        // Filtrer les personnages
        $filteredCharacters = array_filter($characters, function (Character $character) use ($search, $statusFilter, $universeFilter, $contextUniverseFilter, $isSuperAdmin, $allowedUniverseIds) {
            $characterUniverseId = $character->getUniverse()?->getId();

            if (!$isSuperAdmin) {
                if (!$characterUniverseId || !in_array($characterUniverseId, $allowedUniverseIds, true)) {
                    return false;
                }
            }

            // Filtre de recherche
            if ($search) {
                $searchLower = strtolower($search);
                $matchesSearch = 
                    str_contains(strtolower($character->getName()), $searchLower) ||
                    ($character->getActualPseudo() && str_contains(strtolower($character->getActualPseudo()), $searchLower)) ||
                    ($character->getUser() && str_contains(strtolower($character->getUser()->getPseudo()), $searchLower)) ||
                    ($character->getUser() && str_contains(strtolower($character->getUser()->getEmail()), $searchLower));
                if (!$matchesSearch) {
                    return false;
                }
            }

            // Filtre par statut
            if ($statusFilter && $character->getStatus() !== $statusFilter) {
                return false;
            }

            // Filtre par univers
            if ($universeFilter) {
                if (!$characterUniverseId || $characterUniverseId != $universeFilter) {
                    return false;
                }
            }

            if ($contextUniverseFilter) {
                if (!$characterUniverseId || $characterUniverseId != $contextUniverseFilter) {
                    return false;
                }
            }

            return true;
        });

        $data = array_map(fn (Character $character): array => [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'actualPseudo' => $character->getActualPseudo(),
                'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                'status' => $character->getStatus(),
                'universe_id' => $character->getUniverse()?->getId(),
                'universe_name' => $character->getUniverse()?->getName(),
                'user_id' => $character->getUser()?->getId(),
                'user_pseudo' => $character->getUser()?->getPseudo(),
                'user_email' => $character->getUser()?->getEmail(),
                'createdAt' => $character->getCreatedAt()?->format('Y-m-d H:i:s'),
                'validatedAt' => $character->getValidatedAt()?->format('Y-m-d H:i:s'),
            ], array_values($filteredCharacters));

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'api_admin_characters_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->canAccessCharacterModeration($user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        if (!$isSuperAdmin) {
            $allowedUniverseIds = array_map(
                static fn ($universe) => $universe->getId(),
                $user->getAdminUniverses()->toArray()
            );
            $characterUniverseId = $character->getUniverse()?->getId();
            if (!$characterUniverseId || !in_array($characterUniverseId, $allowedUniverseIds, true)) {
                return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
            }
        }

        $data = [
            'id' => $character->getId(),
            'name' => $character->getName(),
            'firstName' => $character->getFirstName(),
            'lastName' => $character->getLastName(),
            'actualPseudo' => $character->getActualPseudo(),
            'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
            'status' => $character->getStatus(),
            'biography' => $character->getBiography(),
            'universe_id' => $character->getUniverse()?->getId(),
            'universe_name' => $character->getUniverse()?->getName(),
            'user_id' => $character->getUser()?->getId(),
            'user_pseudo' => $character->getUser()?->getPseudo(),
            'user_email' => $character->getUser()?->getEmail(),
            'createdAt' => $character->getCreatedAt()?->format('Y-m-d H:i:s'),
            'validatedAt' => $character->getValidatedAt()?->format('Y-m-d H:i:s'),
            'slug' => $character->getSlug(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'api_admin_characters_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->canAccessCharacterModeration($user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user->getAdminUniverses()->toArray()
        );
        $characterUniverseId = $character->getUniverse()?->getId();
        if (!$isSuperAdmin && (!$characterUniverseId || !in_array($characterUniverseId, $allowedUniverseIds, true))) {
            return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $character->setName($data['name']);
        }
        if (isset($data['actualPseudo'])) {
            $character->setActualPseudo($data['actualPseudo']);
        }
        if (isset($data['avatar'])) {
            $character->setAvatar($data['avatar']);
        }
        if (isset($data['status'])) {
            $character->setStatus($data['status']);
            // Si le statut passe à "validated", mettre à jour validatedAt
            if ($data['status'] === Character::STATUS_VALIDATED && !$character->getValidatedAt()) {
                $character->setValidatedAt(new \DateTimeImmutable());
            }
        }
        if (isset($data['biography'])) {
            $character->setBiography($data['biography']);
        }
        if (isset($data['universe_id'])) {
            if ($data['universe_id'] === null) {
                $character->setUniverse(null);
            } else {
                if (!$isSuperAdmin && !in_array((int) $data['universe_id'], $allowedUniverseIds, true)) {
                    return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
                }
                $universe = $this->universRepository->find($data['universe_id']);
                if ($universe) {
                    $character->setUniverse($universe);
                } else {
                    return new JsonResponse(['error' => 'Univers non trouvé'], Response::HTTP_NOT_FOUND);
                }
            }
        }
        if (isset($data['user_id'])) {
            $assigningUser = $data['user_id'] !== null;
            if ($assigningUser && $character->getStatus() === Character::STATUS_ABANDONED && !isset($data['status'])) {
                return new JsonResponse([
                    'error' => 'Pour assigner un personnage abandonné, précisez aussi un nouveau statut (ex. validated ou draft).',
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($data['user_id'] === null) {
                $character->setUser(null);
            } else {
                $characterUser = $this->userRepository->find($data['user_id']);
                if ($characterUser) {
                    $character->setUser($characterUser);
                } else {
                    return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
                }
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Personnage mis à jour avec succès']);
    }

    #[Route('/merge', name: 'api_admin_characters_merge', methods: ['POST'])]
    public function merge(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $survivorId = (int) ($data['survivorId'] ?? 0);
        $absorbedId = (int) ($data['absorbedId'] ?? 0);

        if ($survivorId <= 0 || $absorbedId <= 0) {
            return new JsonResponse(['error' => 'survivorId et absorbedId sont requis'], Response::HTTP_BAD_REQUEST);
        }

        $survivor = $this->characterRepository->find($survivorId);
        $absorbed = $this->characterRepository->find($absorbedId);

        if (!$survivor || !$absorbed) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        if (!$isSuperAdmin) {
            $allowedUniverseIds = array_map(
                static fn ($universe) => $universe->getId(),
                $user->getAdminUniverses()->toArray()
            );
            foreach ([$survivor, $absorbed] as $character) {
                $characterUniverseId = $character->getUniverse()?->getId();
                if (!$characterUniverseId || !in_array($characterUniverseId, $allowedUniverseIds, true)) {
                    return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
                }
            }
        }

        try {
            $reassignedCounts = $this->characterMergeService->merge($survivor, $absorbed);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'message' => 'Personnages fusionnés avec succès',
            'survivorId' => $survivor->getId(),
            'reassignedCounts' => $reassignedCounts,
        ]);
    }

    #[Route('/{id}', name: 'api_admin_characters_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        if (!$isSuperAdmin) {
            $allowedUniverseIds = array_map(
                static fn ($universe) => $universe->getId(),
                $user->getAdminUniverses()->toArray()
            );
            $characterUniverseId = $character->getUniverse()?->getId();
            if (!$characterUniverseId || !in_array($characterUniverseId, $allowedUniverseIds, true)) {
                return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
            }
        }

        $this->entityManager->remove($character);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Personnage supprimé avec succès']);
    }

    #[Route('', name: 'api_admin_characters_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user->getAdminUniverses()->toArray()
        );

        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['user_id'])) {
            return new JsonResponse(['error' => 'L\'utilisateur est requis'], Response::HTTP_BAD_REQUEST);
        }

        $character = new Character();
        $character->setName($data['name']);
        $character->setStatus($data['status'] ?? Character::STATUS_DRAFT);
        $character->setActualPseudo($data['actualPseudo'] ?? null);
        $character->setAvatar($data['avatar'] ?? null);
        $character->setBiography($data['biography'] ?? null);

        if (isset($data['universe_id'])) {
            if (!$isSuperAdmin && !in_array((int) $data['universe_id'], $allowedUniverseIds, true)) {
                return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
            }
            $universe = $this->universRepository->find($data['universe_id']);
            if ($universe) {
                $character->setUniverse($universe);
            } else {
                return new JsonResponse(['error' => 'Univers non trouvé'], Response::HTTP_NOT_FOUND);
            }
        }

        $characterUser = $this->userRepository->find((int) $data['user_id']);
        if (!$characterUser) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $character->setUser($characterUser);

        $this->entityManager->persist($character);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $character->getId(),
            'name' => $character->getName(),
            'status' => $character->getStatus(),
        ], Response::HTTP_CREATED);
    }

    private function canAccessCharacterModeration(User $user): bool
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
            return true;
        }

        $isUniverseScopedModerator = in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_MODERATOR', $roles, true);
        if (!$isUniverseScopedModerator) {
            return false;
        }

        return $user->getAdminUniverses()->count() > 0;
    }
}

