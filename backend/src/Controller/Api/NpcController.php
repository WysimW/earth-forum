<?php

namespace App\Controller\Api;

use App\Entity\Npc;
use App\Repository\NpcRepository;
use App\Repository\UniversRepository;
use App\Repository\ElseworldRepository;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/npcs')]
class NpcController extends AbstractController
{
    public function __construct(
        private NpcRepository $npcRepository,
        private EntityManagerInterface $entityManager,
        private UniversRepository $universRepository,
        private ElseworldRepository $elseworldRepository,
        private ValidatorInterface $validator,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('', name: 'api_npcs_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $npcs = $this->npcRepository->findBy(['user' => $user]);
        $universes = $this->universRepository->findAll();

        // Structure pour regrouper par univers et elseworlds
        $contentByUniverse = [];
        $contentWithoutUniverse = [
            'npcs' => [],
            'total' => 0
        ];

        // Initialiser les tableaux pour chaque univers
        foreach ($universes as $universe) {
            $contentByUniverse[$universe->getId()] = [
                'universe' => [
                    'id' => $universe->getId(),
                    'name' => $universe->getName(),
                    'slug' => $universe->getSlug(),
                ],
                'mainContent' => [
                    'npcs' => [],
                    'total' => 0
                ],
                'elseworlds' => [],
                'total' => 0
            ];
        }

        // Classer les PNJ par univers/elseworld
        foreach ($npcs as $npc) {
            if ($npc->isExcludeFromPersonalNpcs()) {
                continue;
            }

            $universe = $npc->getUniverse();
            $elseworld = $npc->getElseworld();

            $npcData = $this->serializeNpc($npc);

            if ($universe) {
                $universeId = $universe->getId();

                if ($elseworld) {
                    $elseWorldId = $elseworld->getId();

                    if (!isset($contentByUniverse[$universeId]['elseworlds'][$elseWorldId])) {
                        $contentByUniverse[$universeId]['elseworlds'][$elseWorldId] = [
                            'elseworld' => [
                                'id' => $elseworld->getId(),
                                'name' => $elseworld->getName(),
                                'slug' => $elseworld->getSlug(),
                            ],
                            'npcs' => [],
                            'total' => 0
                        ];
                    }

                    $contentByUniverse[$universeId]['elseworlds'][$elseWorldId]['npcs'][] = $npcData;
                    $contentByUniverse[$universeId]['elseworlds'][$elseWorldId]['total']++;
                } else {
                    $contentByUniverse[$universeId]['mainContent']['npcs'][] = $npcData;
                    $contentByUniverse[$universeId]['mainContent']['total']++;
                }

                $contentByUniverse[$universeId]['total']++;
            } else {
                $contentWithoutUniverse['npcs'][] = $npcData;
                $contentWithoutUniverse['total']++;
            }
        }

        // Filtrer les univers qui n'ont pas de PNJ
        $contentByUniverse = array_filter($contentByUniverse, function($item) {
            return $item['total'] > 0;
        });

        return new JsonResponse([
            'contentByUniverse' => array_values($contentByUniverse),
            'contentWithoutUniverse' => $contentWithoutUniverse,
        ]);
    }

    #[Route('/{id}', name: 'api_npcs_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $npc = $this->npcRepository->find($id);
        if (!$npc) {
            return new JsonResponse(['error' => 'PNJ non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est propriétaire ou modérateur
        if ($npc->getUser() !== $user && !$this->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if ($response = $this->blockOwnerAccessFactionDefinitionNpc($npc, $user)) {
            return $response;
        }

        return new JsonResponse($this->serializeNpc($npc, true));
    }

    #[Route('', name: 'api_npcs_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty($data['name'])) {
            return new JsonResponse(['error' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
        }

        $npc = new Npc();
        $npc->setUser($user);
        $npc->setName($data['name']);

        // Définir le statut (draft par défaut)
        $isDraft = $data['isDraft'] ?? true;
        $npc->setStatus($isDraft ? Npc::STATUS_DRAFT : Npc::STATUS_PENDING);

        // Propriétés de base
        if (isset($data['firstName'])) {
            $npc->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $npc->setLastName($data['lastName']);
        }
        if (isset($data['pseudonyms'])) {
            $npc->setPseudonyms($data['pseudonyms']);
        }
        if (isset($data['age'])) {
            $npc->setAge($data['age']);
        }
        if (isset($data['gender'])) {
            $npc->setGender($data['gender']);
        }
        if (isset($data['moralAffiliation'])) {
            $npc->setMoralAffiliation($data['moralAffiliation']);
        }
        if (isset($data['factions'])) {
            $npc->setFactions($data['factions']);
        }
        if (isset($data['occupation'])) {
            $npc->setOccupation($data['occupation']);
        }
        if (isset($data['equipment'])) {
            $npc->setEquipment($data['equipment']);
        }
        if (isset($data['weaknesses'])) {
            $npc->setWeaknesses($data['weaknesses']);
        }
        if (isset($data['avatar'])) {
            $npc->setAvatar($data['avatar']);
        }
        if (isset($data['biography'])) {
            $npc->setBiography($data['biography']);
        }
        if (isset($data['personality'])) {
            $npc->setPersonality($data['personality']);
        }
        if (isset($data['appearance'])) {
            $npc->setAppearance($data['appearance']);
        }
        if (isset($data['abilities'])) {
            $npc->setAbilities($data['abilities']);
        }
        if (isset($data['roleInStory'])) {
            $npc->setRoleInStory($data['roleInStory']);
        }
        if (isset($data['relationships'])) {
            $npc->setRelationships($data['relationships']);
        }
        if (isset($data['quests'])) {
            $npc->setQuests($data['quests']);
        }
        if (isset($data['secrets'])) {
            $npc->setSecrets($data['secrets']);
        }

        // Univers et Elseworld
        if (isset($data['universe_id'])) {
            $universe = $this->universRepository->find($data['universe_id']);
            if ($universe) {
                $npc->setUniverse($universe);
            }
        }

        if (isset($data['elseworld_id'])) {
            $elseworld = $this->elseworldRepository->find($data['elseworld_id']);
            if ($elseworld) {
                $npc->setElseworld($elseworld);
                if (!$npc->getUniverse()) {
                    $npc->setUniverse($elseworld->getParentUniverse());
                }
            }
        }

        // Valider l'entité
        $errors = $this->validator->validate($npc);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['error' => 'Validation échouée', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($npc);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $npc->getId(),
            'message' => $isDraft ? 'PNJ créé en brouillon' : 'PNJ créé',
            'npc' => $this->serializeNpc($npc)
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_npcs_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $npc = $this->npcRepository->find($id);
        if (!$npc) {
            return new JsonResponse(['error' => 'PNJ non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est propriétaire ou modérateur
        if ($npc->getUser() !== $user && !$this->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if ($response = $this->blockOwnerAccessFactionDefinitionNpc($npc, $user)) {
            return $response;
        }

        $data = json_decode($request->getContent(), true);

        // Mettre à jour les propriétés
        if (isset($data['name'])) {
            $npc->setName($data['name']);
        }
        if (isset($data['firstName'])) {
            $npc->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $npc->setLastName($data['lastName']);
        }
        if (isset($data['pseudonyms'])) {
            $npc->setPseudonyms($data['pseudonyms']);
        }
        if (isset($data['age'])) {
            $npc->setAge($data['age']);
        }
        if (isset($data['gender'])) {
            $npc->setGender($data['gender']);
        }
        if (isset($data['moralAffiliation'])) {
            $npc->setMoralAffiliation($data['moralAffiliation']);
        }
        if (isset($data['factions'])) {
            $npc->setFactions($data['factions']);
        }
        if (isset($data['occupation'])) {
            $npc->setOccupation($data['occupation']);
        }
        if (isset($data['equipment'])) {
            $npc->setEquipment($data['equipment']);
        }
        if (isset($data['weaknesses'])) {
            $npc->setWeaknesses($data['weaknesses']);
        }
        if (isset($data['avatar'])) {
            $npc->setAvatar($data['avatar']);
        }
        if (isset($data['biography'])) {
            $npc->setBiography($data['biography']);
        }
        if (isset($data['personality'])) {
            $npc->setPersonality($data['personality']);
        }
        if (isset($data['appearance'])) {
            $npc->setAppearance($data['appearance']);
        }
        if (isset($data['abilities'])) {
            $npc->setAbilities($data['abilities']);
        }
        if (isset($data['roleInStory'])) {
            $npc->setRoleInStory($data['roleInStory']);
        }
        if (isset($data['relationships'])) {
            $npc->setRelationships($data['relationships']);
        }
        if (isset($data['quests'])) {
            $npc->setQuests($data['quests']);
        }
        if (isset($data['secrets'])) {
            $npc->setSecrets($data['secrets']);
        }

        // Gérer le statut
        if (isset($data['status'])) {
            $npc->setStatus($data['status']);
            if ($data['status'] === Npc::STATUS_VALIDATED && !$npc->getValidatedAt()) {
                $npc->setValidatedAt(new \DateTimeImmutable());
            }
        }

        // Univers et Elseworld
        if (isset($data['universe_id'])) {
            if ($data['universe_id'] === null) {
                $npc->setUniverse(null);
            } else {
                $universe = $this->universRepository->find($data['universe_id']);
                if ($universe) {
                    $npc->setUniverse($universe);
                }
            }
        }

        if (isset($data['elseworld_id'])) {
            if ($data['elseworld_id'] === null) {
                $npc->setElseworld(null);
            } else {
                $elseworld = $this->elseworldRepository->find($data['elseworld_id']);
                if ($elseworld) {
                    $npc->setElseworld($elseworld);
                    if (!$npc->getUniverse()) {
                        $npc->setUniverse($elseworld->getParentUniverse());
                    }
                }
            }
        }

        // Valider l'entité
        $errors = $this->validator->validate($npc);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['error' => 'Validation échouée', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'PNJ mis à jour avec succès',
            'npc' => $this->serializeNpc($npc)
        ]);
    }

    #[Route('/{id}', name: 'api_npcs_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $npc = $this->npcRepository->find($id);
        if (!$npc) {
            return new JsonResponse(['error' => 'PNJ non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est propriétaire ou modérateur
        if ($npc->getUser() !== $user && !$this->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if ($response = $this->blockOwnerAccessFactionDefinitionNpc($npc, $user)) {
            return $response;
        }

        $this->entityManager->remove($npc);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'PNJ supprimé avec succès']);
    }

    /**
     * Les PNJ créés comme « définition de faction » ne sont pas modifiables comme des fiches perso (sauf modération).
     */
    private function blockOwnerAccessFactionDefinitionNpc(Npc $npc, $user): ?JsonResponse
    {
        if ($this->isGranted('ROLE_MODERATOR')) {
            return null;
        }

        if ($npc->isExcludeFromPersonalNpcs() && $npc->getUser() === $user) {
            return new JsonResponse([
                'error' => 'Ce PNJ est géré au niveau de la fiche de faction, pas dans « Mes PNJ ».',
            ], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    private function serializeNpc(Npc $npc, bool $detailed = false): array
    {
        $data = [
            'id' => $npc->getId(),
            'name' => $npc->getName(),
            'status' => $npc->getStatus(),
            'statusMessage' => $npc->getStatusMessage(),
            'avatar' => $this->s3MediaUrlResolver->resolve($npc->getAvatar()),
            'universe' => $npc->getUniverse() ? [
                'id' => $npc->getUniverse()->getId(),
                'name' => $npc->getUniverse()->getName(),
                'slug' => $npc->getUniverse()->getSlug(),
            ] : null,
            'elseworld' => $npc->getElseworld() ? [
                'id' => $npc->getElseworld()->getId(),
                'name' => $npc->getElseworld()->getName(),
                'slug' => $npc->getElseworld()->getSlug(),
            ] : null,
            'excludeFromPersonalNpcs' => $npc->isExcludeFromPersonalNpcs(),
        ];

        if ($detailed) {
            $data = array_merge($data, [
                'firstName' => $npc->getFirstName(),
                'lastName' => $npc->getLastName(),
                'pseudonyms' => $npc->getPseudonyms(),
                'age' => $npc->getAge(),
                'gender' => $npc->getGender(),
                'moralAffiliation' => $npc->getMoralAffiliation(),
                'factions' => $npc->getFactions(),
                'occupation' => $npc->getOccupation(),
                'equipment' => $npc->getEquipment(),
                'weaknesses' => $npc->getWeaknesses(),
                'biography' => $npc->getBiography(),
                'personality' => $npc->getPersonality(),
                'appearance' => $npc->getAppearance(),
                'abilities' => $npc->getAbilities(),
                'roleInStory' => $npc->getRoleInStory(),
                'relationships' => $npc->getRelationships(),
                'quests' => $npc->getQuests(),
                'secrets' => $npc->getSecrets(),
                'validatedAt' => $npc->getValidatedAt()?->format('Y-m-d H:i:s'),
                'createdAt' => $npc->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $npc->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]);
        }

        return $data;
    }
}






