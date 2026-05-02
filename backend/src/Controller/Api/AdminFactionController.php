<?php

namespace App\Controller\Api;

use App\Entity\Faction;
use App\Entity\Location;
use App\Entity\Univers;
use App\Repository\FactionRepository;
use App\Repository\LocationRepository;
use App\Repository\UniversRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/factions')]
class AdminFactionController extends AbstractController
{
    public function __construct(
        private FactionRepository $factionRepository,
        private UserRepository $userRepository,
        private UniversRepository $universRepository,
        private LocationRepository $locationRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_admin_factions_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->getUser();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user?->getRoles() ?? [], true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user?->getAdminUniverses()->toArray() ?? []
        );
        $contextUniverseFilter = $request->query->get('context_universe_id');

        if ($contextUniverseFilter && !$isSuperAdmin && !in_array((int) $contextUniverseFilter, $allowedUniverseIds, true)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $search = trim((string) $request->query->get('search', ''));
        $factions = $search !== ''
            ? $this->factionRepository->createQueryBuilder('f')
                ->leftJoin('f.universe', 'u')->addSelect('u')
                ->leftJoin('f.founder', 'founder')->addSelect('founder')
                ->andWhere('LOWER(f.name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%')
                ->orderBy('f.name', 'ASC')
                ->getQuery()
                ->getResult()
            : $this->factionRepository->findBy([], ['name' => 'ASC']);

        $factions = array_values(array_filter($factions, function (Faction $faction) use ($isSuperAdmin, $allowedUniverseIds, $contextUniverseFilter) {
            $universeId = $faction->getUniverse()?->getId();

            if (!$isSuperAdmin) {
                if (!$universeId || !in_array($universeId, $allowedUniverseIds, true)) {
                    return false;
                }
            }

            if ($contextUniverseFilter) {
                return $universeId && (int) $universeId === (int) $contextUniverseFilter;
            }

            return true;
        }));

        return new JsonResponse([
            'factions' => array_map(fn (Faction $faction) => $this->serializeFaction($faction, false), $factions),
            'total' => count($factions),
        ]);
    }

    #[Route('/meta', name: 'api_admin_factions_meta', methods: ['GET'])]
    public function meta(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->getUser();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user?->getRoles() ?? [], true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user?->getAdminUniverses()->toArray() ?? []
        );

        $users = $this->userRepository->findBy([], ['pseudo' => 'ASC']);
        $universes = $isSuperAdmin
            ? $this->universRepository->findBy([], ['name' => 'ASC'])
            : array_values(array_filter(
                $this->universRepository->findBy([], ['name' => 'ASC']),
                static fn (Univers $universe) => in_array($universe->getId(), $allowedUniverseIds, true)
            ));
        $locations = $this->locationRepository->findBy([], ['name' => 'ASC']);

        return new JsonResponse([
            'users' => array_map(static fn ($user) => [
                'id' => $user->getId(),
                'pseudo' => $user->getPseudo(),
                'email' => $user->getEmail(),
            ], $users),
            'universes' => array_map(static fn (Univers $universe) => [
                'id' => $universe->getId(),
                'name' => $universe->getName(),
                'slug' => $universe->getSlug(),
            ], $universes),
            'locations' => array_map(static fn (Location $location) => [
                'id' => $location->getId(),
                'name' => $location->getName(),
            ], $locations),
        ]);
    }

    #[Route('/{id}', name: 'api_admin_factions_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getOne(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user?->getRoles() ?? [], true);
        if (!$isSuperAdmin) {
            $allowedUniverseIds = array_map(
                static fn ($universe) => $universe->getId(),
                $user?->getAdminUniverses()->toArray() ?? []
            );
            $factionUniverseId = $faction->getUniverse()?->getId();
            if (!$factionUniverseId || !in_array($factionUniverseId, $allowedUniverseIds, true)) {
                return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
            }
        }

        return new JsonResponse($this->serializeFaction($faction, true));
    }

    #[Route('', name: 'api_admin_factions_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Payload JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['name']) || empty($data['universe_id']) || empty($data['founder_id'])) {
            return new JsonResponse(['error' => 'Nom, univers et chef de faction sont requis'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user?->getRoles() ?? [], true);
        if (!$isSuperAdmin) {
            $allowedUniverseIds = array_map(
                static fn ($universe) => $universe->getId(),
                $user?->getAdminUniverses()->toArray() ?? []
            );
            if (!in_array((int) $data['universe_id'], $allowedUniverseIds, true)) {
                return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
            }
        }

        $faction = new Faction();
        $faction->setName((string) $data['name']);
        $applyError = $this->applyAdminFactionData($faction, $data, true);
        if ($applyError !== null) {
            return new JsonResponse(['error' => $applyError], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validator->validate($faction);
        if (count($errors) > 0) {
            $details = [];
            foreach ($errors as $error) {
                $details[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['error' => 'Validation échouée', 'details' => $details], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($faction);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Faction créée avec succès',
            'faction' => $this->serializeFaction($faction, true),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_admin_factions_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user?->getRoles() ?? [], true);
        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user?->getAdminUniverses()->toArray() ?? []
        );
        $factionUniverseId = $faction->getUniverse()?->getId();
        if (!$isSuperAdmin && (!$factionUniverseId || !in_array($factionUniverseId, $allowedUniverseIds, true))) {
            return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Payload JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name']) && trim((string) $data['name']) === '') {
            return new JsonResponse(['error' => 'Le nom ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }
        if (isset($data['name'])) {
            $faction->setName((string) $data['name']);
        }

        if (!$isSuperAdmin && array_key_exists('universe_id', $data) && !in_array((int) $data['universe_id'], $allowedUniverseIds, true)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $applyError = $this->applyAdminFactionData($faction, $data, false);
        if ($applyError !== null) {
            return new JsonResponse(['error' => $applyError], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validator->validate($faction);
        if (count($errors) > 0) {
            $details = [];
            foreach ($errors as $error) {
                $details[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['error' => 'Validation échouée', 'details' => $details], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Faction mise à jour avec succès',
            'faction' => $this->serializeFaction($faction, true),
        ]);
    }

    #[Route('/{id}', name: 'api_admin_factions_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user?->getRoles() ?? [], true);
        if (!$isSuperAdmin) {
            $allowedUniverseIds = array_map(
                static fn ($universe) => $universe->getId(),
                $user?->getAdminUniverses()->toArray() ?? []
            );
            $factionUniverseId = $faction->getUniverse()?->getId();
            if (!$factionUniverseId || !in_array($factionUniverseId, $allowedUniverseIds, true)) {
                return new JsonResponse(['error' => 'Univers non autorisé'], Response::HTTP_FORBIDDEN);
            }
        }

        $this->entityManager->remove($faction);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Faction supprimée avec succès']);
    }

    private function applyAdminFactionData(Faction $faction, array $data, bool $requireAll): ?string
    {
        if ($requireAll || array_key_exists('universe_id', $data)) {
            $universe = $this->universRepository->find((int) ($data['universe_id'] ?? 0));
            if (!$universe instanceof Univers) {
                return 'Univers introuvable';
            }
            $faction->setUniverse($universe);
        }

        if ($requireAll || array_key_exists('founder_id', $data)) {
            $founder = $this->userRepository->find((int) ($data['founder_id'] ?? 0));
            if (!$founder) {
                return 'Chef de faction introuvable';
            }
            $faction->setFounder($founder);
        }

        if (array_key_exists('description', $data)) {
            $faction->setDescription($data['description'] ?: null);
        }
        if (array_key_exists('alignment', $data)) {
            $faction->setAlignment($data['alignment'] ?: null);
        }
        if (array_key_exists('scope', $data)) {
            $faction->setScope($data['scope'] ?: null);
        }
        if (array_key_exists('objectives', $data)) {
            $faction->setObjectives($data['objectives'] ?: null);
        }
        if (array_key_exists('headquartersDescription', $data)) {
            $faction->setHeadquartersDescription($data['headquartersDescription'] ?: null);
        }
        if (array_key_exists('logo', $data)) {
            $faction->setLogo($this->normalizeMediaUrl($data['logo'] ?? null));
        }
        if (array_key_exists('icon', $data)) {
            $faction->setIcon($this->normalizeMediaUrl($data['icon'] ?? null));
        }
        if (array_key_exists('status', $data)) {
            $status = (string) $data['status'];
            if (in_array($status, [Faction::STATUS_OPEN, Faction::STATUS_CLOSED], true)) {
                $faction->setStatus($status);
            }
        }

        if (array_key_exists('headquarters_id', $data)) {
            $headquartersId = $data['headquarters_id'];
            if ($headquartersId === null || $headquartersId === '') {
                $faction->setHeadquarters(null);
            } else {
                $location = $this->locationRepository->find((int) $headquartersId);
                if (!$location instanceof Location) {
                    return 'Lieu de quartier général introuvable';
                }
                $faction->setHeadquarters($location);
            }
        }

        return null;
    }

    private function serializeFaction(Faction $faction, bool $detailed): array
    {
        $data = [
            'id' => $faction->getId(),
            'name' => $faction->getName(),
            'slug' => $faction->getSlug(),
            'description' => $faction->getDescription(),
            'alignment' => $faction->getAlignment(),
            'scope' => $faction->getScope(),
            'status' => $faction->getStatus(),
            'objectives' => $faction->getObjectives(),
            'logo' => $faction->getLogo(),
            'icon' => $faction->getIcon(),
            'headquartersDescription' => $faction->getHeadquartersDescription(),
            'universe' => $faction->getUniverse() ? [
                'id' => $faction->getUniverse()->getId(),
                'name' => $faction->getUniverse()->getName(),
                'slug' => $faction->getUniverse()->getSlug(),
            ] : null,
            'founder' => $faction->getFounder() ? [
                'id' => $faction->getFounder()->getId(),
                'pseudo' => $faction->getFounder()->getPseudo(),
            ] : null,
            'headquarters' => $faction->getHeadquarters() ? [
                'id' => $faction->getHeadquarters()->getId(),
                'name' => $faction->getHeadquarters()->getName(),
            ] : null,
            'membersCount' => [
                'characters' => $faction->getCharacters()->count(),
                'npcs' => $faction->getNpcs()->count(),
            ],
        ];

        if ($detailed) {
            $data['characters'] = array_map(static fn ($character) => [
                'id' => $character->getId(),
                'name' => $character->getName(),
            ], $faction->getCharacters()->toArray());
            $data['npcs'] = array_map(static fn ($npc) => [
                'id' => $npc->getId(),
                'name' => $npc->getName(),
            ], $faction->getNpcs()->toArray());
        }

        return $data;
    }

    private function normalizeMediaUrl(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $url = trim($value);
        $withoutQuery = preg_split('/[?#]/', $url)[0] ?? $url;

        return $withoutQuery !== '' ? $withoutQuery : null;
    }
}

