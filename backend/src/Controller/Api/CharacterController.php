<?php

namespace App\Controller\Api;

use App\Entity\Character;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use App\Repository\CharacterRepository;
use App\Repository\UniversRepository;
use App\Repository\ElseworldRepository;
use App\Repository\ForumRepository;
use App\Service\CharacterForumManager;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/characters')]
class CharacterController extends AbstractController
{
    public function __construct(
        private CharacterRepository $characterRepository,
        private EntityManagerInterface $entityManager,
        private UniversRepository $universRepository,
        private ElseworldRepository $elseworldRepository,
        private CharacterForumManager $characterForumManager,
        private ForumRepository $forumRepository,
        private SluggerInterface $slugger,
        private ValidatorInterface $validator,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('', name: 'api_characters_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $characters = $this->characterRepository->findCharactersByUser($user);
        $universes = $this->universRepository->findAll();

        // Structure pour regrouper par univers et elseworlds
        $contentByUniverse = [];
        $contentWithoutUniverse = [
            'characters' => [],
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
                    'characters' => [],
                    'total' => 0
                ],
                'elseworlds' => [],
                'total' => 0
            ];
        }

        // Classer les personnages par univers/elseworld
        foreach ($characters as $character) {
            $universe = $character->getUniverse();
            $elseworld = $character->getElseworld();

            $characterData = $this->serializeCharacter($character);

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
                            'characters' => [],
                            'total' => 0
                        ];
                    }

                    $contentByUniverse[$universeId]['elseworlds'][$elseWorldId]['characters'][] = $characterData;
                    $contentByUniverse[$universeId]['elseworlds'][$elseWorldId]['total']++;
                } else {
                    $contentByUniverse[$universeId]['mainContent']['characters'][] = $characterData;
                    $contentByUniverse[$universeId]['mainContent']['total']++;
                }

                $contentByUniverse[$universeId]['total']++;
            } else {
                $contentWithoutUniverse['characters'][] = $characterData;
                $contentWithoutUniverse['total']++;
            }
        }

        // Filtrer les univers qui n'ont pas de personnages
        $contentByUniverse = array_filter($contentByUniverse, function($item) {
            return $item['total'] > 0;
        });

        return new JsonResponse([
            'contentByUniverse' => array_values($contentByUniverse),
            'contentWithoutUniverse' => $contentWithoutUniverse,
        ]);
    }

    #[Route('/universe/{slug}/selectable', name: 'api_characters_selectable_by_universe', methods: ['GET'])]
    public function selectableByUniverse(string $slug): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $characters = $this->characterRepository->findValidatedByUniverseSlugAndUser($slug, $user);

        $items = array_map(function (Character $character): array {
            return [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'firstName' => $character->getFirstName(),
                'lastName' => $character->getLastName(),
                'actualPseudo' => $character->getActualPseudo(),
                'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                'alias' => $character->getAlias(),
                'moralAffiliation' => $character->getMoralAffiliation(),
                'occupation' => $character->getOccupation(),
                'age' => $character->getAge(),
                'gender' => $character->getGender(),
                'factions' => $character->getFactions(),
                'universe' => $character->getUniverse() ? [
                    'id' => $character->getUniverse()->getId(),
                    'name' => $character->getUniverse()->getName(),
                    'slug' => $character->getUniverse()->getSlug(),
                ] : null,
                'elseworld' => $character->getElseworld() ? [
                    'id' => $character->getElseworld()->getId(),
                    'name' => $character->getElseworld()->getName(),
                    'slug' => $character->getElseworld()->getSlug(),
                ] : null,
            ];
        }, $characters);

        return new JsonResponse([
            'items' => $items,
            'total' => count($items),
        ]);
    }

    #[Route('/universe/{slug}/validated', name: 'api_characters_validated_by_universe', methods: ['GET'])]
    public function validatedByUniverse(string $slug): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $characters = $this->characterRepository->findValidatedByUniverseSlug($slug);

        $items = array_map(function (Character $character): array {
            return [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                'moralAffiliation' => $character->getMoralAffiliation(),
                'user' => $character->getUser() ? [
                    'id' => $character->getUser()->getId(),
                    'pseudo' => $character->getUser()->getPseudo(),
                ] : null,
                'universe' => $character->getUniverse() ? [
                    'id' => $character->getUniverse()->getId(),
                    'name' => $character->getUniverse()->getName(),
                    'slug' => $character->getUniverse()->getSlug(),
                ] : null,
                'elseworld' => $character->getElseworld() ? [
                    'id' => $character->getElseworld()->getId(),
                    'name' => $character->getElseworld()->getName(),
                    'slug' => $character->getElseworld()->getSlug(),
                ] : null,
            ];
        }, $characters);

        return new JsonResponse([
            'items' => $items,
            'total' => count($items),
        ]);
    }

    #[Route('/{id}', name: 'api_characters_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if ($character->isEventCharacter()) {
            return new JsonResponse(['error' => 'Ce personnage est réservé au contexte event'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que l'utilisateur est propriétaire ou modérateur
        if ($character->getUser() !== $user && !$this->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->serializeCharacter($character, true));
    }

    #[Route('', name: 'api_characters_create', methods: ['POST'])]
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

        $character = new Character();
        $character->setUser($user);
        $character->setName($data['name']);

        // Définir le statut (draft par défaut si isDraft est true ou non spécifié)
        $isDraft = $data['isDraft'] ?? true;
        $character->setStatus($isDraft ? Character::STATUS_DRAFT : Character::STATUS_PENDING);
        $character->setStatusMessage($isDraft ? 'En cours de rédaction' : 'En attente de validation');

        // Propriétés de base
        if (isset($data['firstName'])) {
            $character->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $character->setLastName($data['lastName']);
        }
        if (isset($data['pseudonyms'])) {
            $character->setPseudonyms($data['pseudonyms']);
        }
        if (isset($data['actualPseudo'])) {
            $character->setActualPseudo($data['actualPseudo']);
        }
        if (isset($data['age'])) {
            $character->setAge($data['age']);
        }
        if (isset($data['gender'])) {
            $character->setGender($data['gender']);
        }
        if (isset($data['sexualOrientation'])) {
            $character->setSexualOrientation($data['sexualOrientation']);
        }
        if (isset($data['moralAffiliation'])) {
            $character->setMoralAffiliation($data['moralAffiliation']);
        }
        if (isset($data['factions'])) {
            $character->setFactions($data['factions']);
        }
        if (isset($data['civilStatus'])) {
            $character->setCivilStatus($data['civilStatus']);
        }
        if (isset($data['occupation'])) {
            $character->setOccupation($data['occupation']);
        }
        if (isset($data['equipment'])) {
            $character->setEquipment($data['equipment']);
        }
        if (isset($data['weaknesses'])) {
            $character->setWeaknesses($data['weaknesses']);
        }
        if (isset($data['avatar'])) {
            $character->setAvatar($data['avatar']);
        }
        if (isset($data['biography'])) {
            $character->setBiography($data['biography']);
        }
        if (isset($data['personality'])) {
            $character->setPersonality($data['personality']);
        }
        if (isset($data['appearance'])) {
            $character->setAppearance($data['appearance']);
        }
        if (isset($data['abilities'])) {
            $character->setAbilities($data['abilities']);
        }
        if (isset($data['alias'])) {
            $character->setAlias($data['alias']);
        }

        // Univers et Elseworld
        if (isset($data['universe_id'])) {
            $universe = $this->universRepository->find($data['universe_id']);
            if ($universe) {
                $character->setUniverse($universe);
            }
        }

        if (isset($data['elseworld_id'])) {
            $elseworld = $this->elseworldRepository->find($data['elseworld_id']);
            if ($elseworld) {
                $character->setElseworld($elseworld);
                // S'assurer que l'univers parent est défini
                if (!$character->getUniverse()) {
                    $character->setUniverse($elseworld->getParentUniverse());
                }
            }
        }

        // Valider l'entité
        $errors = $this->validator->validate($character);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['error' => 'Validation échouée', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($character);

        // Créer le thread de fiche seulement si ce n'est pas un brouillon ou si on soumet
        if (!$isDraft) {
            $this->createCharacterSheetThread($character);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $character->getId(),
            'message' => $isDraft ? 'Personnage créé en brouillon' : 'Personnage créé et soumis pour validation',
            'character' => $this->serializeCharacter($character)
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_characters_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if ($character->isEventCharacter()) {
            return new JsonResponse(['error' => 'Ce personnage est réservé au contexte event'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que l'utilisateur est propriétaire ou modérateur
        if ($character->getUser() !== $user && !$this->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $oldStatus = $character->getStatus();

        // Mettre à jour les propriétés
        if (isset($data['name'])) {
            $character->setName($data['name']);
        }
        if (isset($data['firstName'])) {
            $character->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $character->setLastName($data['lastName']);
        }
        if (isset($data['pseudonyms'])) {
            $character->setPseudonyms($data['pseudonyms']);
        }
        if (isset($data['actualPseudo'])) {
            $character->setActualPseudo($data['actualPseudo']);
        }
        if (isset($data['age'])) {
            $character->setAge($data['age']);
        }
        if (isset($data['gender'])) {
            $character->setGender($data['gender']);
        }
        if (isset($data['sexualOrientation'])) {
            $character->setSexualOrientation($data['sexualOrientation']);
        }
        if (isset($data['moralAffiliation'])) {
            $character->setMoralAffiliation($data['moralAffiliation']);
        }
        if (isset($data['factions'])) {
            $character->setFactions($data['factions']);
        }
        if (isset($data['civilStatus'])) {
            $character->setCivilStatus($data['civilStatus']);
        }
        if (isset($data['occupation'])) {
            $character->setOccupation($data['occupation']);
        }
        if (isset($data['equipment'])) {
            $character->setEquipment($data['equipment']);
        }
        if (isset($data['weaknesses'])) {
            $character->setWeaknesses($data['weaknesses']);
        }
        if (isset($data['avatar'])) {
            $character->setAvatar($data['avatar']);
        }
        if (isset($data['biography'])) {
            $character->setBiography($data['biography']);
        }
        if (isset($data['personality'])) {
            $character->setPersonality($data['personality']);
        }
        if (isset($data['appearance'])) {
            $character->setAppearance($data['appearance']);
        }
        if (isset($data['abilities'])) {
            $character->setAbilities($data['abilities']);
        }
        if (isset($data['alias'])) {
            $character->setAlias($data['alias']);
        }
        if (isset($data['sheetTheme'])) {
            $character->setSheetTheme($data['sheetTheme']);
        }

        // Gérer le statut
        if (isset($data['status'])) {
            $character->setStatus($data['status']);
            if ($data['status'] === Character::STATUS_VALIDATED && $oldStatus !== Character::STATUS_VALIDATED) {
                $character->setValidatedAt(new \DateTimeImmutable());
            }
        }

        // Si le personnage était validé et qu'on le modifie, passer en editing
        if ($oldStatus === Character::STATUS_VALIDATED && isset($data['status']) && $data['status'] !== Character::STATUS_VALIDATED) {
            $character->setStatus(Character::STATUS_EDITING);
            $character->setStatusMessage('Modifications en cours');
        }

        // Univers et Elseworld
        if (isset($data['universe_id'])) {
            if ($data['universe_id'] === null) {
                $character->setUniverse(null);
            } else {
                $universe = $this->universRepository->find($data['universe_id']);
                if ($universe) {
                    $character->setUniverse($universe);
                }
            }
        }

        if (isset($data['elseworld_id'])) {
            if ($data['elseworld_id'] === null) {
                $character->setElseworld(null);
            } else {
                $elseworld = $this->elseworldRepository->find($data['elseworld_id']);
                if ($elseworld) {
                    $character->setElseworld($elseworld);
                    if (!$character->getUniverse()) {
                        $character->setUniverse($elseworld->getParentUniverse());
                    }
                }
            }
        }

        // Valider l'entité
        $errors = $this->validator->validate($character);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['error' => 'Validation échouée', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour le thread de fiche si il existe
        $characterSheetThread = $character->getMainCharacterSheetThread();
        if ($characterSheetThread) {
            $targetForum = $this->getForumByCharacterStatus($character->getStatus(), $character->getUniverse());
            if ($targetForum) {
                $characterSheetThread->setForum($targetForum);
            }
        } else {
            // Créer le thread si il n'existe pas et que le statut n'est pas draft
            if ($character->getStatus() !== Character::STATUS_DRAFT) {
                $this->createCharacterSheetThread($character);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Personnage mis à jour avec succès',
            'character' => $this->serializeCharacter($character)
        ]);
    }

    #[Route('/{id}', name: 'api_characters_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if ($character->isEventCharacter()) {
            return new JsonResponse(['error' => 'Ce personnage est réservé au contexte event'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que l'utilisateur est propriétaire ou modérateur
        if ($character->getUser() !== $user && !$this->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($character);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Personnage supprimé avec succès']);
    }

    #[Route('/{id}/submit', name: 'api_characters_submit', methods: ['POST'])]
    public function submit(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if ($character->isEventCharacter()) {
            return new JsonResponse(['error' => 'Ce personnage est réservé au contexte event'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que l'utilisateur est propriétaire
        if ($character->getUser() !== $user) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Changer le statut de draft à pending
        $character->setStatus(Character::STATUS_PENDING);
        $character->setStatusMessage('En attente de validation');

        // Créer le thread de fiche si il n'existe pas
        if (!$character->getMainCharacterSheetThread()) {
            $this->createCharacterSheetThread($character);
        } else {
            // Déplacer vers le forum "en attente"
            $targetForum = $this->getForumByCharacterStatus(Character::STATUS_PENDING, $character->getUniverse());
            if ($targetForum) {
                $character->getMainCharacterSheetThread()->setForum($targetForum);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Personnage soumis pour validation',
            'character' => $this->serializeCharacter($character)
        ]);
    }

    #[Route('/{id}/validate', name: 'api_characters_validate', methods: ['POST'])]
    public function validate(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if ($character->isEventCharacter()) {
            return new JsonResponse(['error' => 'Ce personnage est réservé au contexte event'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->canModerateCharacter($user, $character)) {
            return new JsonResponse(['error' => 'Accès refusé pour cet univers'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $moderationNote = $data['moderationNote'] ?? '';
        $preambule = $data['preambule'] ?? '';
        $conclusion = $data['conclusion'] ?? '';

        $character->setStatus(Character::STATUS_VALIDATED);
        $character->setStatusMessage('Personnage validé');
        $character->setValidatedAt(new \DateTimeImmutable());

        // Déplacer le thread vers le forum "Fiches validées"
        $characterSheetThread = $character->getMainCharacterSheetThread();
        if ($characterSheetThread) {
            $targetForum = $this->getForumByCharacterStatus(Character::STATUS_VALIDATED, $character->getUniverse());
            if ($targetForum) {
                $characterSheetThread->setForum($targetForum);
            }

            // Ajouter un post de modération
            $preambuleHtml = $preambule ? '<div class="moderation-preambule">' . nl2br(htmlspecialchars($preambule)) . '</div>' : '';
            $noteHtml = $moderationNote ? nl2br(htmlspecialchars($moderationNote)) : '';
            $conclusionHtml = $conclusion ? '<div class="moderation-conclusion">' . nl2br(htmlspecialchars($conclusion)) . '</div>' : '';
            
            $moderationPost = new Post();
            $moderationPost->setThread($characterSheetThread);
            $moderationPost->setAuthor($user);
            $moderationPost->setContent('<div class="moderation-alert moderation-alert-success">
                <div class="moderation-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <span class="moderation-title">Personnage validé par ' . htmlspecialchars($user->getPseudo()) . '</span>
                </div>
                ' . ($preambuleHtml ? $preambuleHtml : '') . '
                ' . ($noteHtml ? $noteHtml : '') . '
                ' . ($conclusionHtml ? $conclusionHtml : '') . '
            </div>');
            $moderationPost->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($moderationPost);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Personnage validé avec succès',
            'character' => $this->serializeCharacter($character, true)
        ]);
    }

    #[Route('/{id}/reject', name: 'api_characters_reject', methods: ['POST'])]
    public function reject(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if ($character->isEventCharacter()) {
            return new JsonResponse(['error' => 'Ce personnage est réservé au contexte event'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->canModerateCharacter($user, $character)) {
            return new JsonResponse(['error' => 'Accès refusé pour cet univers'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $rejectionReason = $data['rejectionReason'] ?? 'Personnage rejeté';
        $moderationNote = $data['moderationNote'] ?? '';
        $preambule = $data['preambule'] ?? '';
        $conclusion = $data['conclusion'] ?? '';

        $character->setStatus(Character::STATUS_REJECTED);
        $character->setStatusMessage($rejectionReason);

        // Déplacer le thread vers le forum "Fiches refusées"
        $characterSheetThread = $character->getMainCharacterSheetThread();
        if ($characterSheetThread) {
            $targetForum = $this->getForumByCharacterStatus(Character::STATUS_REJECTED, $character->getUniverse());
            if ($targetForum) {
                $characterSheetThread->setForum($targetForum);
            }

            // Ajouter un post de modération
            $preambuleHtml = $preambule ? '<div class="moderation-preambule">' . nl2br(htmlspecialchars($preambule)) . '</div>' : '';
            $rejectionReasonHtml = '<div class="moderation-rejection-reason"><strong>Raison du rejet:</strong> ' . htmlspecialchars($rejectionReason) . '</div>';
            $noteHtml = $moderationNote ? nl2br(htmlspecialchars($moderationNote)) : '';
            $conclusionHtml = $conclusion ? '<div class="moderation-conclusion">' . nl2br(htmlspecialchars($conclusion)) . '</div>' : '';
            
            $moderationPost = new Post();
            $moderationPost->setThread($characterSheetThread);
            $moderationPost->setAuthor($user);
            $moderationPost->setContent('<div class="moderation-alert moderation-alert-danger">
                <div class="moderation-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    <span class="moderation-title">Personnage rejeté par ' . htmlspecialchars($user->getPseudo()) . '</span>
                </div>
                ' . ($preambuleHtml ? $preambuleHtml : '') . '
                ' . $rejectionReasonHtml . '
                ' . ($noteHtml ? $noteHtml : '') . '
                ' . ($conclusionHtml ? $conclusionHtml : '') . '
            </div>');
            $moderationPost->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($moderationPost);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Personnage rejeté',
            'character' => $this->serializeCharacter($character, true)
        ]);
    }

    #[Route('/{id}/needs-revision', name: 'api_characters_needs_revision', methods: ['POST'])]
    public function needsRevision(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $character = $this->characterRepository->find($id);
        if (!$character) {
            return new JsonResponse(['error' => 'Personnage non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if ($character->isEventCharacter()) {
            return new JsonResponse(['error' => 'Ce personnage est réservé au contexte event'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->canModerateCharacter($user, $character)) {
            return new JsonResponse(['error' => 'Accès refusé pour cet univers'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $moderationNote = $data['moderationNote'] ?? '';
        $fieldsToModify = $data['fieldsToModify'] ?? []; // Array de champs à modifier
        $fieldCitations = $data['fieldCitations'] ?? []; // Object avec citations par champ
        $preambule = $data['preambule'] ?? '';
        $conclusion = $data['conclusion'] ?? '';

        $character->setStatus(Character::STATUS_EDITING);
        $character->setStatusMessage('Points à modifier');

        // Stocker les informations de modération dans statusMessage ou créer un champ dédié
        $moderationData = [
            'note' => $moderationNote,
            'fields' => $fieldsToModify,
            'citations' => $fieldCitations,
            'moderator' => $user->getPseudo(),
            'date' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ];
        $character->setModerationNote(json_encode($moderationData));

        // Ajouter un post de modération avec les détails
        $characterSheetThread = $character->getMainCharacterSheetThread();
        if ($characterSheetThread) {
            // Mapping des IDs de champs vers des labels lisibles
            $fieldLabels = [
                'name' => 'Nom affiché',
                'firstName' => 'Prénom',
                'lastName' => 'Nom de famille',
                'avatar' => 'Avatar',
                'biography' => 'Biographie',
                'personality' => 'Personnalité',
                'appearance' => 'Apparence',
                'abilities' => 'Capacités',
                'equipment' => 'Équipements',
                'weaknesses' => 'Faiblesses',
                'age' => 'Âge',
                'gender' => 'Genre',
                'moralAffiliation' => 'Affiliation morale',
                'factions' => 'Factions',
                'occupation' => 'Occupation',
            ];
            
            $fieldsHtml = '';
            if (!empty($fieldsToModify)) {
                $fieldsHtml = '<div class="moderation-fields">';
                foreach ($fieldsToModify as $field) {
                    $fieldLabel = $fieldLabels[$field] ?? $field;
                    $citation = isset($fieldCitations[$field]) && !empty($fieldCitations[$field]) 
                        ? '<div class="moderation-citation"><strong>Citation:</strong> <em>' . htmlspecialchars($fieldCitations[$field]) . '</em></div>' 
                        : '';
                    $fieldsHtml .= '<div class="moderation-field-item">';
                    $fieldsHtml .= '<div class="moderation-field-name">' . htmlspecialchars($fieldLabel) . '</div>';
                    if ($citation) {
                        $fieldsHtml .= $citation;
                    }
                    $fieldsHtml .= '</div>';
                }
                $fieldsHtml .= '</div>';
            }
            
            $preambuleHtml = $preambule ? '<div class="moderation-preambule">' . nl2br(htmlspecialchars($preambule)) . '</div>' : '';
            $conclusionHtml = $conclusion ? '<div class="moderation-conclusion">' . nl2br(htmlspecialchars($conclusion)) . '</div>' : '';
            $noteHtml = $moderationNote ? nl2br(htmlspecialchars($moderationNote)) : '';
            
            $moderationPost = new Post();
            $moderationPost->setThread($characterSheetThread);
            $moderationPost->setAuthor($user);
            $moderationPost->setContent('<div class="moderation-alert moderation-alert-warning">
                <div class="moderation-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    <span class="moderation-title">Points à modifier par ' . htmlspecialchars($user->getPseudo()) . '</span>
                </div>
                ' . ($preambuleHtml ? $preambuleHtml : '') . '
                ' . ($fieldsHtml ? '<div class="moderation-section"><strong>Champs à modifier:</strong>' . $fieldsHtml . '</div>' : '') . '
                ' . ($conclusionHtml ? $conclusionHtml : '') . '
                ' . ($noteHtml ? $noteHtml : '') . '
            </div>');
            $moderationPost->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($moderationPost);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Personnage marqué comme nécessitant des modifications',
            'character' => $this->serializeCharacter($character, true)
        ]);
    }

    private function createCharacterSheetThread(Character $character): void
    {
        // S'assurer que les forums existent
        $forums = $this->characterForumManager->ensureCharacterForumsExistForUniverse($character->getUniverse());
        $pendingForum = $forums['pending'];

        // Créer le thread de fiche
        $characterSheetThread = new Thread();
        $title = 'Fiche de ' . $character->getName();
        if ($character->getElseworld()) {
            $title .= ' (Elseworld: ' . $character->getElseworld()->getName() . ')';
        }
        $characterSheetThread->setTitle($title);
        $characterSheetThread->setAuthor($this->getUser());
        $characterSheetThread->setForum($pendingForum);
        $characterSheetThread->setType('character_sheet');
        $characterSheetThread->setStatus('open');
        $characterSheetThread->setCharacterSheet($character);
        $characterSheetThread->setSlug($this->slugger->slug('fiche-' . $character->getName())->lower());
        $characterSheetThread->setUniverse($character->getUniverse());
        
        if ($character->getElseworld() && method_exists($characterSheetThread, 'setElseworld')) {
            $characterSheetThread->setElseworld($character->getElseworld());
        }

        // Créer le premier post avec les détails du personnage
        $initialPost = new Post();
        $initialPost->setThread($characterSheetThread);
        $initialPost->setAuthor($this->getUser());
        
        // Générer le contenu HTML de la fiche
        $content = $this->generateCharacterSheetContent($character);
        $initialPost->setContent($content);

        $this->entityManager->persist($characterSheetThread);
        $this->entityManager->persist($initialPost);
    }

    private function generateCharacterSheetContent(Character $character): string
    {
        // Générer un contenu HTML simple pour la fiche
        $html = '<div class="character-sheet-container">';
        $html .= '<div class="character-sheet-header">';
        $html .= '<h1>' . htmlspecialchars($character->getName()) . '</h1>';
        
        if ($character->getAvatar()) {
            $avatarSrc = $this->s3MediaUrlResolver->resolve($character->getAvatar()) ?? $character->getAvatar();
            $html .= '<img src="' . htmlspecialchars((string) $avatarSrc) . '" alt="' . htmlspecialchars($character->getName()) . '" style="max-width: 200px; border-radius: 50%;">';
        }
        
        $html .= '</div>';
        
        if ($character->getBiography()) {
            $html .= '<div class="character-section"><h2>Biographie</h2><div>' . $character->getBiography() . '</div></div>';
        }
        if ($character->getPersonality()) {
            $html .= '<div class="character-section"><h2>Personnalité</h2><div>' . $character->getPersonality() . '</div></div>';
        }
        if ($character->getAppearance()) {
            $html .= '<div class="character-section"><h2>Apparence</h2><div>' . $character->getAppearance() . '</div></div>';
        }
        if ($character->getAbilities()) {
            $html .= '<div class="character-section"><h2>Capacités</h2><div>' . $character->getAbilities() . '</div></div>';
        }
        if ($character->getEquipment()) {
            $html .= '<div class="character-section"><h2>Équipements</h2><div>' . $character->getEquipment() . '</div></div>';
        }
        if ($character->getWeaknesses()) {
            $html .= '<div class="character-section"><h2>Faiblesses</h2><div>' . $character->getWeaknesses() . '</div></div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    private function getForumByCharacterStatus(string $status, ?\App\Entity\Univers $universe = null): ?\App\Entity\Forum
    {
        $forumName = match($status) {
            Character::STATUS_DRAFT, Character::STATUS_EDITING, Character::STATUS_PENDING => 'Fiches en attente',
            Character::STATUS_VALIDATED => 'Fiches validées',
            Character::STATUS_REJECTED, Character::STATUS_ABANDONED => 'Fiches refusées',
            default => null,
        };

        if (!$forumName) {
            return null;
        }

        $criteria = ['name' => $forumName];
        if ($universe) {
            $criteria['universe'] = $universe;
        }

        return $this->forumRepository->findOneBy($criteria);
    }

    private function serializeCharacter(Character $character, bool $detailed = false): array
    {
        $data = [
            'id' => $character->getId(),
            'name' => $character->getName(),
            'status' => $character->getStatus(),
            'statusMessage' => $character->getStatusMessage(),
            'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
            'firstName' => $character->getFirstName(),
            'lastName' => $character->getLastName(),
            'universe' => $character->getUniverse() ? [
                'id' => $character->getUniverse()->getId(),
                'name' => $character->getUniverse()->getName(),
                'slug' => $character->getUniverse()->getSlug(),
            ] : null,
            'elseworld' => $character->getElseworld() ? [
                'id' => $character->getElseworld()->getId(),
                'name' => $character->getElseworld()->getName(),
                'slug' => $character->getElseworld()->getSlug(),
            ] : null,
            'factions' => $character->getFactions(),
            'moralAffiliation' => $character->getMoralAffiliation(),
            'sheetTheme' => $character->getSheetTheme(),
        ];

        if ($detailed) {
            $data = array_merge($data, [
                'pseudonyms' => $character->getPseudonyms(),
                'actualPseudo' => $character->getActualPseudo(),
                'age' => $character->getAge(),
                'gender' => $character->getGender(),
                'sexualOrientation' => $character->getSexualOrientation(),
                'civilStatus' => $character->getCivilStatus(),
                'occupation' => $character->getOccupation(),
                'equipment' => $character->getEquipment(),
                'weaknesses' => $character->getWeaknesses(),
                'biography' => $character->getBiography(),
                'personality' => $character->getPersonality(),
                'appearance' => $character->getAppearance(),
                'abilities' => $character->getAbilities(),
                'alias' => $character->getAlias(),
                'sheetTheme' => $character->getSheetTheme(),
                'validatedAt' => $character->getValidatedAt()?->format('Y-m-d H:i:s'),
                'createdAt' => $character->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $character->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]);
        }

        return $data;
    }

    private function canModerateCharacter(User $user, Character $character): bool
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
            return true;
        }

        $isScopedModerator = in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_MODERATOR', $roles, true);
        if (!$isScopedModerator) {
            return false;
        }

        $characterUniverseId = $character->getUniverse()?->getId();
        if (!$characterUniverseId) {
            return false;
        }

        $allowedUniverseIds = array_map(
            static fn ($universe) => $universe->getId(),
            $user->getAdminUniverses()->toArray()
        );

        return in_array($characterUniverseId, $allowedUniverseIds, true);
    }
}
