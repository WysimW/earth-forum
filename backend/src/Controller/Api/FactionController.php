<?php

namespace App\Controller\Api;

use App\Entity\Character;
use App\Entity\Faction;
use App\Entity\FactionCharacterApplication;
use App\Entity\FactionCharacterMembership;
use App\Entity\Npc;
use App\Entity\User;
use App\Repository\FactionCharacterApplicationRepository;
use App\Repository\CharacterRepository;
use App\Repository\FactionCharacterMembershipRepository;
use App\Repository\FactionRepository;
use App\Repository\NpcRepository;
use App\Repository\UniversRepository;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/factions')]
class FactionController extends AbstractController
{
    public function __construct(
        private FactionRepository $factionRepository,
        private CharacterRepository $characterRepository,
        private FactionCharacterApplicationRepository $applicationRepository,
        private FactionCharacterMembershipRepository $membershipRepository,
        private NpcRepository $npcRepository,
        private UniversRepository $universRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('', name: 'api_factions_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $query = $this->factionRepository->createQueryBuilder('f')
            ->leftJoin('f.universe', 'u')
            ->addSelect('u')
            ->leftJoin('f.founder', 'founder')
            ->addSelect('founder')
            ->orderBy('f.name', 'ASC');

        $search = trim((string) $request->query->get('search', ''));
        if ($search !== '') {
            $query
                ->andWhere('LOWER(f.name) LIKE :search OR LOWER(f.description) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $universe = $request->query->get('universe');
        if (is_string($universe) && $universe !== '') {
            if (ctype_digit($universe)) {
                $query->andWhere('u.id = :universeId')->setParameter('universeId', (int) $universe);
            } else {
                $query->andWhere('u.slug = :universeSlug')->setParameter('universeSlug', $universe);
            }
        }

        $factions = $query->getQuery()->getResult();

        return new JsonResponse([
            'factions' => array_map(fn (Faction $faction) => $this->serializeFaction($faction), $factions),
            'total' => count($factions),
        ]);
    }

    #[Route('/{identifier}', name: 'api_factions_get', methods: ['GET'], requirements: ['identifier' => '[A-Za-z0-9\-]+'])]
    public function getOne(string $identifier): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = ctype_digit($identifier)
            ? $this->factionRepository->find((int) $identifier)
            : $this->factionRepository->findOneBy(['slug' => $identifier]);

        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serializeFaction($faction, true));
    }

    #[Route('/{id}/available-characters', name: 'api_factions_available_characters', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function availableCharacters(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $universe = $faction->getUniverse();
        if ($universe === null) {
            return new JsonResponse(['characters' => []]);
        }

        $characters = array_values(array_filter(
            $this->characterRepository->findByUnivers($universe),
            static fn (Character $c) => !$c->isEventCharacter()
        ));
        $existingMemberIds = [];
        foreach ($faction->getCharacters() as $member) {
            $existingMemberIds[] = $member->getId();
        }

        $availableCharacters = array_values(array_filter(
            array_map(
                static fn (Character $character) => [
                    'id' => $character->getId(),
                    'name' => $character->getName(),
                    'slug' => $character->getSlug(),
                    'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                    'moralAffiliation' => $character->getMoralAffiliation(),
                    'status' => $character->getStatus(),
                    'universe' => $character->getUniverse() ? [
                        'id' => $character->getUniverse()?->getId(),
                        'name' => $character->getUniverse()?->getName(),
                        'slug' => $character->getUniverse()?->getSlug(),
                    ] : null,
                    'user' => $character->getUser() ? [
                        'id' => $character->getUser()?->getId(),
                        'pseudo' => $character->getUser()?->getPseudo(),
                    ] : null,
                ],
                $characters
            ),
            static fn (array $character) => !in_array($character['id'], $existingMemberIds, true)
        ));

        return new JsonResponse([
            'characters' => $availableCharacters,
        ]);
    }

    #[Route('/{id}/my-applicable-characters', name: 'api_factions_my_applicable_characters', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function myApplicableCharacters(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $characters = $this->characterRepository->findBy(['user' => $user], ['name' => 'ASC']);
        $eligibleCharacters = [];

        $factionUniverse = $faction->getUniverse();

        foreach ($characters as $character) {
            if ($character->isEventCharacter()) {
                continue;
            }

            if ($factionUniverse !== null) {
                $characterUniverseId = $this->resolveCharacterUniverseId($character);
                if ($characterUniverseId !== $factionUniverse->getId()) {
                    continue;
                }
            }

            if ($faction->getCharacters()->contains($character)) {
                continue;
            }

            $application = $this->applicationRepository->findOneByFactionAndCharacter($faction, $character);
            if ($application instanceof FactionCharacterApplication && $application->getStatus() === FactionCharacterApplication::STATUS_PENDING) {
                continue;
            }

            $eligibleCharacters[] = [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                'status' => $character->getStatus(),
                'moralAffiliation' => $character->getMoralAffiliation(),
                'kind' => $character->getKind(),
                'universe' => $character->getUniverse() ? [
                    'id' => $character->getUniverse()->getId(),
                    'name' => $character->getUniverse()->getName(),
                    'slug' => $character->getUniverse()->getSlug(),
                ] : ($character->getElseworld()?->getParentUniverse() ? [
                    'id' => $character->getElseworld()->getParentUniverse()->getId(),
                    'name' => $character->getElseworld()->getParentUniverse()->getName(),
                    'slug' => $character->getElseworld()->getParentUniverse()->getSlug(),
                ] : null),
            ];
        }

        return new JsonResponse([
            'characters' => $eligibleCharacters,
        ]);
    }

    #[Route('', name: 'api_factions_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->canCreateFaction()) {
            return new JsonResponse(['error' => 'Vous n\'êtes pas autorisé à créer une faction'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Payload JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['name']) || !isset($data['universe_id'])) {
            return new JsonResponse(['error' => 'Le nom et l\'univers sont requis'], Response::HTTP_BAD_REQUEST);
        }

        $universe = $this->universRepository->find((int) $data['universe_id']);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], Response::HTTP_BAD_REQUEST);
        }

        $faction = new Faction();
        $faction->setName((string) $data['name']);
        $faction->setUniverse($universe);
        $faction->setFounder($user);
        $this->applyFactionData($faction, $data);

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

    #[Route('/{id}', name: 'api_factions_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
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

        if (isset($data['universe_id'])) {
            $universe = $this->universRepository->find((int) $data['universe_id']);
            if (!$universe) {
                return new JsonResponse(['error' => 'Univers introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $faction->setUniverse($universe);
        }

        $this->applyFactionData($faction, $data);

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

    #[Route('/{id}', name: 'api_factions_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($faction);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Faction supprimée avec succès']);
    }

    #[Route('/{id}/members/characters/{characterId}', name: 'api_factions_add_character', methods: ['POST'], requirements: ['id' => '\d+', 'characterId' => '\d+'])]
    public function addCharacterMember(int $id, int $characterId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($character->isEventCharacter()) {
            return new JsonResponse(['error' => 'Les personnages événementiels ne peuvent pas être ajoutés comme membres de faction'], Response::HTTP_BAD_REQUEST);
        }

        $faction->addCharacter($character);

        $membership = $this->membershipRepository->findOneByFactionAndCharacter($faction, $character);
        if (!$membership instanceof FactionCharacterMembership) {
            $membership = new FactionCharacterMembership();
            $membership->setFaction($faction);
            $membership->setCharacter($character);
            $faction->addCharacterMembership($membership);
            $character->addFactionMembership($membership);
            $this->entityManager->persist($membership);
        }

        $application = $this->applicationRepository->findOneByFactionAndCharacter($faction, $character);
        if ($application instanceof FactionCharacterApplication && $application->getStatus() === FactionCharacterApplication::STATUS_PENDING) {
            $application->setStatus(FactionCharacterApplication::STATUS_ACCEPTED);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Personnage ajouté à la faction',
            'faction' => $this->serializeFaction($faction, true),
        ]);
    }

    #[Route('/{id}/members/characters/{characterId}', name: 'api_factions_remove_character', methods: ['DELETE'], requirements: ['id' => '\d+', 'characterId' => '\d+'])]
    public function removeCharacterMember(int $id, int $characterId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $faction->removeCharacter($character);

        $membership = $this->membershipRepository->findOneByFactionAndCharacter($faction, $character);
        if ($membership instanceof FactionCharacterMembership) {
            $this->entityManager->remove($membership);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Personnage retiré de la faction',
            'faction' => $this->serializeFaction($faction, true),
        ]);
    }

    #[Route('/{id}/members/npcs/{npcId}', name: 'api_factions_add_npc', methods: ['POST'], requirements: ['id' => '\d+', 'npcId' => '\d+'])]
    public function addNpcMember(int $id, int $npcId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $npc = $this->npcRepository->find($npcId);
        if (!$npc instanceof Npc) {
            return new JsonResponse(['error' => 'PNJ non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $faction->addNpc($npc);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'PNJ ajouté à la faction',
            'faction' => $this->serializeFaction($faction, true),
        ]);
    }

    /**
     * Crée un PNJ rattaché à la faction uniquement (n'apparaît pas dans « Mes PNJ » du créateur).
     */
    #[Route('/{id}/npcs', name: 'api_factions_create_faction_npc', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createFactionDefinitionNpc(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return new JsonResponse(['error' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
        }

        $npc = new Npc();
        $npc->setUser($user);
        $npc->setName($name);
        $npc->setExcludeFromPersonalNpcs(true);
        $npc->setStatus(Npc::STATUS_PENDING);

        $occupation = isset($data['occupation']) ? trim((string) $data['occupation']) : '';
        if ($occupation !== '') {
            $npc->setOccupation($occupation);
        }

        if (isset($data['avatar'])) {
            $avatar = trim((string) $data['avatar']);
            if ($avatar !== '') {
                $npc->setAvatar($avatar);
            }
        }

        $factionUniverse = $faction->getUniverse();
        if ($factionUniverse !== null) {
            $npc->setUniverse($factionUniverse);
        }

        $errors = $this->validator->validate($npc);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath().': '.$error->getMessage();
            }

            return new JsonResponse(['error' => 'Validation échouée', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $faction->addNpc($npc);
        $this->entityManager->persist($npc);
        $this->entityManager->flush();

        $npc->setStatus(Npc::STATUS_VALIDATED);
        $npc->setValidatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $npc->getId(),
            'message' => 'PNJ de faction créé et validé',
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/members/npcs/{npcId}', name: 'api_factions_remove_npc', methods: ['DELETE'], requirements: ['id' => '\d+', 'npcId' => '\d+'])]
    public function removeNpcMember(int $id, int $npcId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $npc = $this->npcRepository->find($npcId);
        if (!$npc instanceof Npc) {
            return new JsonResponse(['error' => 'PNJ non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $faction->removeNpc($npc);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'PNJ retiré de la faction',
            'faction' => $this->serializeFaction($faction, true),
        ]);
    }

    #[Route('/{id}/members/characters/{characterId}/role', name: 'api_factions_update_character_role', methods: ['PUT'], requirements: ['id' => '\d+', 'characterId' => '\d+'])]
    public function updateCharacterRole(int $id, int $characterId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $membership = $this->membershipRepository->findOneByFactionAndCharacter($faction, $character);
        if (!$membership instanceof FactionCharacterMembership) {
            if (!$faction->getCharacters()->contains($character)) {
                return new JsonResponse(['error' => 'Ce personnage n\'est pas membre de la faction'], Response::HTTP_NOT_FOUND);
            }

            $membership = new FactionCharacterMembership();
            $membership->setFaction($faction);
            $membership->setCharacter($character);
            $faction->addCharacterMembership($membership);
            $character->addFactionMembership($membership);
            $this->entityManager->persist($membership);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !array_key_exists('roleRp', $data)) {
            return new JsonResponse(['error' => 'Le champ roleRp est requis'], Response::HTTP_BAD_REQUEST);
        }

        $roleRp = is_string($data['roleRp']) ? trim($data['roleRp']) : '';
        $membership->setRoleRp($roleRp !== '' ? $roleRp : null);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Rôle RP mis à jour',
            'faction' => $this->serializeFaction($faction, true),
        ]);
    }

    #[Route('/{id}/applications', name: 'api_factions_apply', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function applyToFaction(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$faction->isOpen()) {
            return new JsonResponse(['error' => 'Les candidatures sont fermées pour cette faction'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $characterId = (int) ($data['characterId'] ?? 0);
        if ($characterId <= 0) {
            return new JsonResponse(['error' => 'Le personnage est requis'], Response::HTTP_BAD_REQUEST);
        }

        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($character->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Vous ne pouvez postuler qu\'avec vos personnages'], Response::HTTP_FORBIDDEN);
        }

        if ($character->isEventCharacter()) {
            return new JsonResponse(['error' => 'Les personnages événementiels ne peuvent pas postuler à une faction'], Response::HTTP_BAD_REQUEST);
        }

        $factionUniverse = $faction->getUniverse();
        if ($factionUniverse !== null) {
            $characterUniverseId = $this->resolveCharacterUniverseId($character);
            if ($characterUniverseId !== $factionUniverse->getId()) {
                return new JsonResponse(['error' => 'Ce personnage n’appartient pas à l’univers de cette faction'], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($faction->getCharacters()->contains($character)) {
            return new JsonResponse(['error' => 'Ce personnage est déjà membre de la faction'], Response::HTTP_BAD_REQUEST);
        }

        $application = $this->applicationRepository->findOneByFactionAndCharacter($faction, $character);
        if ($application instanceof FactionCharacterApplication) {
            if ($application->getStatus() === FactionCharacterApplication::STATUS_PENDING) {
                return new JsonResponse(['error' => 'Une candidature est déjà en attente pour ce personnage'], Response::HTTP_CONFLICT);
            }

            $application->setStatus(FactionCharacterApplication::STATUS_PENDING);
        } else {
            $application = new FactionCharacterApplication();
            $application->setFaction($faction);
            $application->setCharacter($character);
            $faction->addCharacterApplication($application);
            $character->addFactionApplication($application);
            $this->entityManager->persist($application);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Candidature envoyée au chef de faction',
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/applications', name: 'api_factions_applications_list', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function listApplications(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'applications' => $this->serializePendingApplications($faction),
        ]);
    }

    #[Route('/{id}/applications/{applicationId}/accept', name: 'api_factions_applications_accept', methods: ['POST'], requirements: ['id' => '\d+', 'applicationId' => '\d+'])]
    public function acceptApplication(int $id, int $applicationId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $application = $this->applicationRepository->find($applicationId);
        if (!$application instanceof FactionCharacterApplication || $application->getFaction()?->getId() !== $faction->getId()) {
            return new JsonResponse(['error' => 'Candidature introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($application->getStatus() !== FactionCharacterApplication::STATUS_PENDING) {
            return new JsonResponse(['error' => 'Cette candidature a déjà été traitée'], Response::HTTP_BAD_REQUEST);
        }

        $character = $application->getCharacter();
        if (!$character instanceof Character) {
            return new JsonResponse(['error' => 'Personnage introuvable'], Response::HTTP_NOT_FOUND);
        }

        $faction->addCharacter($character);
        $application->setStatus(FactionCharacterApplication::STATUS_ACCEPTED);

        $membership = $this->membershipRepository->findOneByFactionAndCharacter($faction, $character);
        if (!$membership instanceof FactionCharacterMembership) {
            $membership = new FactionCharacterMembership();
            $membership->setFaction($faction);
            $membership->setCharacter($character);
            $faction->addCharacterMembership($membership);
            $character->addFactionMembership($membership);
            $this->entityManager->persist($membership);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Candidature acceptée',
            'faction' => $this->serializeFaction($faction, true),
        ]);
    }

    #[Route('/{id}/applications/{applicationId}/reject', name: 'api_factions_applications_reject', methods: ['POST'], requirements: ['id' => '\d+', 'applicationId' => '\d+'])]
    public function rejectApplication(int $id, int $applicationId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $faction = $this->factionRepository->find($id);
        if (!$faction instanceof Faction) {
            return new JsonResponse(['error' => 'Faction non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canManageFaction($faction, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $application = $this->applicationRepository->find($applicationId);
        if (!$application instanceof FactionCharacterApplication || $application->getFaction()?->getId() !== $faction->getId()) {
            return new JsonResponse(['error' => 'Candidature introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($application->getStatus() !== FactionCharacterApplication::STATUS_PENDING) {
            return new JsonResponse(['error' => 'Cette candidature a déjà été traitée'], Response::HTTP_BAD_REQUEST);
        }

        $application->setStatus(FactionCharacterApplication::STATUS_REJECTED);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Candidature refusée',
        ]);
    }

    private function applyFactionData(Faction $faction, array $data): void
    {
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
        if (array_key_exists('logo', $data)) {
            $faction->setLogo($this->normalizeMediaUrl($data['logo'] ?? null));
        }
        if (array_key_exists('icon', $data)) {
            $faction->setIcon($this->normalizeMediaUrl($data['icon'] ?? null));
        }
        if (array_key_exists('headquartersDescription', $data)) {
            $faction->setHeadquartersDescription($data['headquartersDescription'] ?: null);
        }
        if (array_key_exists('status', $data)) {
            $status = (string) $data['status'];
            if (in_array($status, [Faction::STATUS_OPEN, Faction::STATUS_CLOSED], true)) {
                $faction->setStatus($status);
            }
        }
    }

    private function canManageFaction(Faction $faction, User $user): bool
    {
        return $faction->getFounder()?->getId() === $user->getId();
    }

    private function serializeFaction(Faction $faction, bool $detailed = false): array
    {
        $currentUser = $this->getUser();
        $isOwner = $faction->getFounder()?->getId() === $currentUser?->getId();
        $hasMyCharacter = false;
        if ($currentUser instanceof User) {
            foreach ($faction->getCharacters() as $character) {
                if ($character->getUser()?->getId() === $currentUser->getId()) {
                    $hasMyCharacter = true;
                    break;
                }
            }
        }

        $data = [
            'id' => $faction->getId(),
            'name' => $faction->getName(),
            'slug' => $faction->getSlug(),
            'description' => $faction->getDescription(),
            'alignment' => $faction->getAlignment(),
            'scope' => $faction->getScope(),
            'status' => $faction->getStatus(),
            'objectives' => $faction->getObjectives(),
            'logo' => $this->s3MediaUrlResolver->resolve($faction->getLogo()),
            'icon' => $this->s3MediaUrlResolver->resolve($faction->getIcon()),
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
            'canEdit' => $isOwner,
            'isOwner' => $isOwner,
            'hasMyCharacter' => $hasMyCharacter,
            'isMine' => $isOwner || $hasMyCharacter,
            'membersCount' => [
                'characters' => $faction->getCharacters()->count(),
                'npcs' => $faction->getNpcs()->count(),
            ],
        ];

        if ($detailed) {
            $data['characters'] = $this->serializeCharacterMembers($faction);
            $data['npcs'] = array_map(
                static fn (Npc $npc) => [
                    'id' => $npc->getId(),
                    'name' => $npc->getName(),
                    'slug' => $npc->getSlug(),
                    'user' => $npc->getUser() ? [
                        'id' => $npc->getUser()->getId(),
                        'pseudo' => $npc->getUser()->getPseudo(),
                    ] : null,
                ],
                $faction->getNpcs()->toArray()
            );
            $data['applications'] = $data['canEdit'] ? $this->serializePendingApplications($faction) : [];
        }

        return $data;
    }

    private function serializeCharacterMembers(Faction $faction): array
    {
        $membersByCharacterId = [];

        foreach ($faction->getCharacterMemberships() as $membership) {
            $character = $membership->getCharacter();
            if (!$character instanceof Character) {
                continue;
            }

            $membersByCharacterId[$character->getId()] = [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'slug' => $character->getSlug(),
                'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                'moralAffiliation' => $character->getMoralAffiliation(),
                'status' => $character->getStatus(),
                'roleRp' => $membership->getRoleRp(),
                'universe' => $character->getUniverse() ? [
                    'id' => $character->getUniverse()?->getId(),
                    'name' => $character->getUniverse()?->getName(),
                    'slug' => $character->getUniverse()?->getSlug(),
                ] : null,
                'user' => $character->getUser() ? [
                    'id' => $character->getUser()?->getId(),
                    'pseudo' => $character->getUser()?->getPseudo(),
                ] : null,
            ];
        }

        // Compatibilité avec les anciennes données M2M sans rôle RP enregistré.
        foreach ($faction->getCharacters() as $character) {
            if (isset($membersByCharacterId[$character->getId()])) {
                continue;
            }

            $membersByCharacterId[$character->getId()] = [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'slug' => $character->getSlug(),
                'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                'moralAffiliation' => $character->getMoralAffiliation(),
                'status' => $character->getStatus(),
                'roleRp' => null,
                'universe' => $character->getUniverse() ? [
                    'id' => $character->getUniverse()?->getId(),
                    'name' => $character->getUniverse()?->getName(),
                    'slug' => $character->getUniverse()?->getSlug(),
                ] : null,
                'user' => $character->getUser() ? [
                    'id' => $character->getUser()?->getId(),
                    'pseudo' => $character->getUser()?->getPseudo(),
                ] : null,
            ];
        }

        return array_values($membersByCharacterId);
    }

    private function serializePendingApplications(Faction $faction): array
    {
        $applications = $this->applicationRepository->findPendingByFaction($faction);

        return array_map(
            static function (FactionCharacterApplication $application): array {
                $character = $application->getCharacter();

                return [
                    'id' => $application->getId(),
                    'status' => $application->getStatus(),
                    'createdAt' => $application->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'character' => $character ? [
                        'id' => $character->getId(),
                        'name' => $character->getName(),
                        'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                        'moralAffiliation' => $character->getMoralAffiliation(),
                        'status' => $character->getStatus(),
                        'user' => $character->getUser() ? [
                            'id' => $character->getUser()?->getId(),
                            'pseudo' => $character->getUser()?->getPseudo(),
                        ] : null,
                    ] : null,
                ];
            },
            $applications
        );
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

    /**
     * Identifiant d’univers « effectif » du personnage (univers direct ou parent de l’elseworld).
     */
    private function resolveCharacterUniverseId(Character $character): ?int
    {
        $universe = $character->getUniverse();
        if ($universe !== null) {
            return $universe->getId();
        }

        $elseworld = $character->getElseworld();
        if ($elseworld !== null && $elseworld->getParentUniverse() !== null) {
            return $elseworld->getParentUniverse()->getId();
        }

        return null;
    }
}

