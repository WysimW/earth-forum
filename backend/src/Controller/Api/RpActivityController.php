<?php

namespace App\Controller\Api;

use App\Entity\Character;
use App\Entity\Faction;
use App\Entity\Forum;
use App\Entity\Npc;
use App\Entity\Post;
use App\Entity\RpActivity;
use App\Entity\RpActivityRegistration;
use App\Entity\Thread;
use App\Entity\Univers;
use App\Entity\User;
use App\Repository\CharacterRepository;
use App\Repository\FactionRepository;
use App\Repository\ForumRepository;
use App\Repository\NpcRepository;
use App\Repository\RpActivityRegistrationRepository;
use App\Repository\RpActivityRepository;
use App\Repository\ThreadRepository;
use App\Repository\UserRpActivityViewRepository;
use App\Repository\UniversRepository;
use App\Repository\UserRepository;
use App\Service\RpActivityRulesService;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api')]
class RpActivityController extends AbstractController
{
    private const EVENT_DISCUSSION_PARENT_FORUM_NAME = 'Plateforme joueur';
    private const EVENT_DISCUSSION_SUBFORUM_NAME = 'Mission et Évènements';

    public function __construct(
        private readonly RpActivityRepository $rpActivityRepository,
        private readonly RpActivityRegistrationRepository $registrationRepository,
        private readonly UniversRepository $universRepository,
        private readonly FactionRepository $factionRepository,
        private readonly ForumRepository $forumRepository,
        private readonly ThreadRepository $threadRepository,
        private readonly CharacterRepository $characterRepository,
        private readonly NpcRepository $npcRepository,
        private readonly UserRepository $userRepository,
        private readonly UserRpActivityViewRepository $activityViewRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RpActivityRulesService $rulesService,
        private readonly SluggerInterface $slugger,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('/universes/{slug}/rp-activities', name: 'api_rp_activities_by_universe', methods: ['GET'])]
    public function listForUniverse(string $slug, Request $request): JsonResponse
    {
        $universe = $this->universRepository->findOneBy(['slug' => $slug]);
        if (!$universe instanceof Univers) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $filters = [
            'kind' => trim((string) $request->query->get('kind', '')),
            'status' => trim((string) $request->query->get('status', '')),
            'factionId' => (int) $request->query->get('faction', 0),
        ];

        $activities = $this->rpActivityRepository->findForUniverseSlug($slug, $filters);

        /** @var User|null $currentUser */
        $currentUser = $this->getUser() instanceof User ? $this->getUser() : null;
        $participatingOnly = filter_var($request->query->get('participating', false), FILTER_VALIDATE_BOOLEAN);

        $items = [];
        $activityIds = array_map(static fn (RpActivity $activity): int => (int) $activity->getId(), $activities);
        $seenAtByActivityId = $currentUser
            ? $this->activityViewRepository->getSeenAtByActivityIds($currentUser, $activityIds)
            : [];
        foreach ($activities as $activity) {
            if ($participatingOnly && !$currentUser) {
                continue;
            }

            $serialized = $this->serializeActivity($activity, $currentUser);
            $activityId = (int) $activity->getId();
            $seenAt = $seenAtByActivityId[$activityId] ?? null;
            $updatedAt = $activity->getUpdatedAt() ?? $activity->getCreatedAt();
            $isUnread = false;
            if ($currentUser && $updatedAt) {
                $isUnread = !$seenAt || $seenAt < $updatedAt;
                if ((int) ($activity->getCreatedBy()?->getId() ?? 0) === (int) $currentUser->getId()) {
                    $isUnread = false;
                }
            }
            $serialized['isUnread'] = $isUnread;
            if ($participatingOnly && empty($serialized['userRegistration']['isRegistered'])) {
                continue;
            }

            $items[] = $serialized;
        }

        return new JsonResponse([
            'universe' => [
                'id' => $universe->getId(),
                'name' => $universe->getName(),
                'slug' => $universe->getSlug(),
            ],
            'permissions' => [
                'canCreate' => $currentUser ? $this->canManageUniverse($currentUser, $universe) : false,
            ],
            'items' => $items,
            'hasUnread' => count(array_filter($items, static fn (array $item): bool => !empty($item['isUnread']))) > 0,
            'total' => count($items),
        ]);
    }

    #[Route('/rp-activities/{id}', name: 'api_rp_activity_detail', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser() instanceof User ? $this->getUser() : null;
        if ($currentUser) {
            $this->activityViewRepository->markSeen($currentUser, $activity);
        }
        $payload = $this->serializeActivity($activity, $currentUser, true);
        $payload['isUnread'] = false;
        return new JsonResponse($payload);
    }

    #[Route('/rp-activities/{id}/mark-seen', name: 'api_rp_activity_mark_seen', methods: ['POST'])]
    public function markSeen(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->activityViewRepository->markSeen($user, $activity);
        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/universes/{slug}/rp-activities/mark-seen', name: 'api_rp_activities_mark_seen_universe', methods: ['POST'])]
    public function markUniverseSeen(string $slug): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $universe = $this->universRepository->findOneBy(['slug' => $slug]);
        if (!$universe instanceof Univers) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $activities = $this->rpActivityRepository->findForUniverseSlug($slug, []);
        $this->activityViewRepository->markManySeen($user, $activities);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/rp-activities', name: 'api_rp_activity_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $activity = new RpActivity();
        $activity->setCreatedBy($user);

        $error = $this->applyPayloadToActivity($activity, $payload);
        if ($error !== null) {
            return $error;
        }

        if (!$this->canManageUniverse($user, $activity->getUniverse())) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        if (in_array($activity->getKind(), [RpActivity::KIND_EVENT, RpActivity::KIND_MISSION], true)) {
            $this->createEventDiscussionThread($activity, $user);
            $this->entityManager->flush();
        }

        return new JsonResponse(
            ['item' => $this->serializeActivity($activity, $user, true)],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/rp-activities/{id}', name: 'api_rp_activity_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $error = $this->applyPayloadToActivity($activity, $payload);
        if ($error !== null) {
            return $error;
        }

        $this->entityManager->flush();

        return new JsonResponse(['item' => $this->serializeActivity($activity, $user, true)]);
    }

    #[Route('/rp-activities/{id}', name: 'api_rp_activity_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($activity);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'deleted']);
    }

    #[Route('/rp-activities/{id}/threads', name: 'api_rp_activity_link_threads', methods: ['PUT'])]
    public function updateLinkedThreads(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $threadIds = is_array($payload['threadIds'] ?? null) ? $payload['threadIds'] : null;
        if ($threadIds === null) {
            return new JsonResponse(['error' => 'threadIds requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        foreach ($activity->getThreads()->toArray() as $thread) {
            $activity->removeThread($thread);
        }

        foreach ($threadIds as $threadId) {
            $thread = $this->threadRepository->find((int) $threadId);
            if ($thread instanceof Thread) {
                $activity->addThread($thread);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['item' => $this->serializeActivity($activity, $user, true)]);
    }

    #[Route('/rp-activities/{id}/thread-context', name: 'api_rp_activity_thread_context', methods: ['GET'])]
    public function getThreadContext(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $forums = $this->forumRepository->createQueryBuilder('f')
            ->andWhere('f.universe = :universe')
            ->andWhere('f.type = :type')
            ->andWhere('f.status = :status')
            ->setParameter('universe', $activity->getUniverse())
            ->setParameter('type', 'roleplay')
            ->setParameter('status', 'open')
            ->orderBy('f.position', 'ASC')
            ->addOrderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();

        $characters = $this->getSelectableCharactersForActivity($activity, $user);
        $factionNpcs = $this->getFactionNpcsForMissionActivity($activity);

        return new JsonResponse([
            'forums' => array_map(
                static fn (Forum $forum): array => [
                    'id' => $forum->getId(),
                    'name' => $forum->getName(),
                    'slug' => $forum->getSlug(),
                    'parentId' => $forum->getParent()?->getId(),
                ],
                $forums
            ),
            'characters' => array_map(
                static fn (Character $character): array => [
                    'id' => $character->getId(),
                    'name' => $character->getName(),
                    'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                    'kind' => $character->getKind(),
                    'entityType' => 'character',
                ],
                $characters
            ),
            'npcs' => array_map(fn (Npc $npc): array => $this->serializeSelectableNpc($npc), $factionNpcs),
        ]);
    }

    #[Route('/rp-activities/{id}/event-characters', name: 'api_rp_activity_event_characters_list', methods: ['GET'])]
    public function listEventCharacters(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->serializeEventCharactersContext($activity));
    }

    #[Route('/rp-activities/{id}/event-characters', name: 'api_rp_activity_event_characters_create', methods: ['POST'])]
    public function createEventCharacter(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return new JsonResponse(['error' => 'Le nom est requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $character = new Character();
        $character->setName($name);
        $character->setAvatar($this->normalizeMediaUrl($payload['avatar'] ?? null));
        $character->setKind(Character::KIND_EVENT);
        $character->setStatus(Character::STATUS_VALIDATED);
        $character->setStatusMessage('Personnage event');
        $character->setUniverse($activity->getUniverse());
        $character->setEventActivity($activity);
        $character->setUser($activity->getCreatedBy() ?? $user);
        $character->setValidatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($character);
        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 'created',
            'item' => [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                'kind' => $character->getKind(),
            ],
            'context' => $this->serializeEventCharactersContext($activity),
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/rp-activities/{id}/event-characters/{characterId}', name: 'api_rp_activity_event_characters_update', methods: ['PUT'])]
    public function updateEventCharacter(int $id, int $characterId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character || (int) ($character->getEventActivity()?->getId() ?? 0) !== (int) $activity->getId()) {
            return new JsonResponse(['error' => 'Personnage event introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('name', $payload)) {
            $name = trim((string) $payload['name']);
            if ($name === '') {
                return new JsonResponse(['error' => 'Le nom est requis'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $character->setName($name);
        }

        if (array_key_exists('avatar', $payload)) {
            $character->setAvatar($this->normalizeMediaUrl($payload['avatar'] ?? null));
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 'updated',
            'item' => [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                'kind' => $character->getKind(),
            ],
            'context' => $this->serializeEventCharactersContext($activity),
        ]);
    }

    #[Route('/rp-activities/{id}/event-characters/{characterId}', name: 'api_rp_activity_event_characters_delete', methods: ['DELETE'])]
    public function deleteEventCharacter(int $id, int $characterId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character || (int) ($character->getEventActivity()?->getId() ?? 0) !== (int) $activity->getId()) {
            return new JsonResponse(['error' => 'Personnage event introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$character->getPosts()->isEmpty() || !$character->getThreads()->isEmpty() || !$character->getRegistrations()->isEmpty()) {
            return new JsonResponse(
                ['error' => 'Ce personnage event est utilisé (posts, threads ou inscriptions) et ne peut pas être supprimé.'],
                JsonResponse::HTTP_CONFLICT
            );
        }

        $this->entityManager->remove($character);
        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 'deleted',
            'context' => $this->serializeEventCharactersContext($activity),
        ]);
    }

    #[Route('/rp-activities/{id}/event-character-access', name: 'api_rp_activity_event_character_access', methods: ['PUT'])]
    public function updateEventCharacterAccess(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $userIds = is_array($payload['userIds'] ?? null) ? $payload['userIds'] : null;
        if ($userIds === null) {
            return new JsonResponse(['error' => 'userIds requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        foreach ($activity->getAllowedUsers()->toArray() as $allowedUser) {
            $activity->removeAllowedUser($allowedUser);
        }

        foreach ($userIds as $rawId) {
            $candidate = $this->userRepository->find((int) $rawId);
            if ($candidate instanceof User && (int) $candidate->getId() !== (int) ($activity->getCreatedBy()?->getId() ?? 0)) {
                $activity->addAllowedUser($candidate);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 'updated',
            'context' => $this->serializeEventCharactersContext($activity),
        ]);
    }

    #[Route('/rp-activities/{id}/selectable-characters', name: 'api_rp_activity_selectable_characters', methods: ['GET'])]
    public function getSelectableCharacters(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $characters = $this->getSelectableCharactersForActivity($activity, $user);
        $factionNpcs = $this->getFactionNpcsForMissionActivity($activity);

        return new JsonResponse([
            'items' => array_map(
                static fn (Character $character): array => [
                    'id' => $character->getId(),
                    'name' => $character->getName(),
                    'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                    'kind' => $character->getKind(),
                    'entityType' => 'character',
                ],
                $characters
            ),
            'npcs' => array_map(fn (Npc $npc): array => $this->serializeSelectableNpc($npc), $factionNpcs),
            'total' => count($characters),
            'npcTotal' => count($factionNpcs),
        ]);
    }

    #[Route('/rp-activities/{id}/threads/create', name: 'api_rp_activity_create_thread', methods: ['POST'])]
    public function createThreadForActivity(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $content = trim((string) ($payload['content'] ?? ''));
        $forumId = (int) ($payload['forumId'] ?? 0);
        $characterId = (int) ($payload['characterId'] ?? 0);
        $npcId = (int) ($payload['npcId'] ?? 0);

        if ($title === '' || $content === '' || $forumId <= 0) {
            return new JsonResponse(['error' => 'title, content et forumId sont requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (($characterId <= 0 && $npcId <= 0) || ($characterId > 0 && $npcId > 0)) {
            return new JsonResponse(['error' => 'Indiquez soit characterId soit npcId (un seul des deux)'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $forum = $this->forumRepository->find($forumId);
        if (!$forum instanceof Forum) {
            return new JsonResponse(['error' => 'Forum introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }
        if ($forum->getUniverse()?->getId() !== $activity->getUniverse()?->getId()) {
            return new JsonResponse(['error' => 'Le forum doit appartenir au même univers que l’activité'], JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($forum->getType() !== 'roleplay' || $forum->getStatus() !== 'open') {
            return new JsonResponse(['error' => 'Le forum doit être un forum RP ouvert'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $character = null;
        $npc = null;

        if ($npcId > 0) {
            $npc = $this->resolveFactionNpcForActivity($activity, $npcId);
            if (!$npc instanceof Npc) {
                return new JsonResponse(['error' => 'PNJ de faction invalide pour cette mission'], JsonResponse::HTTP_BAD_REQUEST);
            }
        } else {
            $selectableCharacters = $this->getSelectableCharactersForActivity($activity, $user);
            foreach ($selectableCharacters as $selectableCharacter) {
                if ((int) $selectableCharacter->getId() === $characterId) {
                    $character = $selectableCharacter;
                    break;
                }
            }

            if (!$character instanceof Character) {
                return new JsonResponse(['error' => 'Personnage invalide pour la création du thread'], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        $thread = new Thread();
        $thread->setTitle($title);
        $thread->setSlug($this->generateUniqueThreadSlug($title));
        $thread->setForum($forum);
        $thread->setUniverse($activity->getUniverse());
        $thread->setAuthor($user);
        $thread->setType('roleplay');

        if ($character instanceof Character) {
            $thread->setCharacterCreator($character);
            $thread->addParticipant($character);
        }

        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($user);
        $post->setType('roleplay');
        $post->setContent($content);

        if ($character instanceof Character) {
            $post->setCharacter($character);
        }

        if ($npc instanceof Npc) {
            $thread->addNpc($npc);
            $post->addNpc($npc);
        }

        $activity->addThread($thread);

        $this->entityManager->persist($thread);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 'created',
            'thread' => [
                'id' => $thread->getId(),
                'slug' => $thread->getSlug(),
                'title' => $thread->getTitle(),
            ],
            'item' => $this->serializeActivity($activity, $user, true),
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/rp-activities/{id}/register', name: 'api_rp_activity_register', methods: ['POST'])]
    public function register(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        $characterId = (int) ($payload['characterId'] ?? 0);
        if ($characterId <= 0) {
            return new JsonResponse(['error' => 'characterId requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character) {
            return new JsonResponse(['error' => 'Personnage introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($character->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Vous ne pouvez inscrire que vos personnages'], JsonResponse::HTTP_FORBIDDEN);
        }

        if (!$this->rulesService->canCharacterRegister($character, $activity)) {
            return new JsonResponse(['error' => 'Ce personnage ne peut pas soumettre une inscription à cette activité'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $existing = $this->registrationRepository->findOneBy([
            'activity' => $activity,
            'character' => $character,
        ]);

        if ($existing instanceof RpActivityRegistration && $existing->isRegistered()) {
            return new JsonResponse(['error' => 'Ce personnage est déjà validé sur cette activité'], JsonResponse::HTTP_CONFLICT);
        }

        if ($existing instanceof RpActivityRegistration && $existing->isPending()) {
            return new JsonResponse(['error' => 'Ce personnage a déjà une inscription en attente'], JsonResponse::HTTP_CONFLICT);
        }

        $registration = $existing instanceof RpActivityRegistration ? $existing : new RpActivityRegistration();
        $registration->setActivity($activity);
        $registration->setCharacter($character);
        $registration->setStatus(RpActivityRegistration::STATUS_PENDING);
        $registration->setRegisteredAt(new \DateTimeImmutable());
        $registration->setWithdrawnAt(null);

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'pending'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/rp-activities/{id}/register/{characterId}', name: 'api_rp_activity_unregister', methods: ['DELETE'])]
    public function unregister(int $id, int $characterId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $character = $this->characterRepository->find($characterId);
        if (!$character instanceof Character) {
            return new JsonResponse(['error' => 'Personnage introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $isPrivileged = $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_MODERATOR')
            || $this->canEditActivity($user, $activity);
        if ($character->getUser()?->getId() !== $user->getId() && !$isPrivileged) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $registration = $this->registrationRepository->findActiveByActivityAndCharacter($activity, $character);
        if (!$registration instanceof RpActivityRegistration) {
            return new JsonResponse(['error' => 'Inscription active introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $registration->setStatus(RpActivityRegistration::STATUS_WITHDRAWN);
        $registration->setWithdrawnAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'unregistered']);
    }

    #[Route('/rp-activities/{id}/registrations/{registrationId}/approve', name: 'api_rp_activity_approve_registration', methods: ['POST'])]
    public function approveRegistration(int $id, int $registrationId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $activity = $this->rpActivityRepository->find($id);
        if (!$activity instanceof RpActivity) {
            return new JsonResponse(['error' => 'Activité introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->canEditActivity($user, $activity)) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $registration = $this->registrationRepository->find($registrationId);
        if (!$registration instanceof RpActivityRegistration || $registration->getActivity()?->getId() !== $activity->getId()) {
            return new JsonResponse(['error' => 'Inscription introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$registration->isPending()) {
            return new JsonResponse(['error' => 'Seules les inscriptions en attente peuvent être validées'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $registration->setStatus(RpActivityRegistration::STATUS_REGISTERED);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'approved']);
    }

    private function applyPayloadToActivity(RpActivity $activity, array $payload): ?JsonResponse
    {
        if (array_key_exists('title', $payload)) {
            $title = trim((string) $payload['title']);
            if ($title === '') {
                return new JsonResponse(['error' => 'Le titre est requis'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $activity->setTitle($title);
            if (!$activity->getSlug()) {
                $activity->setSlug($this->generateUniqueSlug($title));
            }
        }

        if ($activity->getTitle() === null || trim($activity->getTitle()) === '') {
            return new JsonResponse(['error' => 'Le titre est requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('slug', $payload)) {
            $slug = trim((string) $payload['slug']);
            if ($slug === '') {
                return new JsonResponse(['error' => 'Slug invalide'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $activity->setSlug($slug);
        }

        if (!$activity->getSlug()) {
            $activity->setSlug($this->generateUniqueSlug($activity->getTitle()));
        }

        if (array_key_exists('kind', $payload)) {
            $activity->setKind((string) $payload['kind']);
        }

        if (array_key_exists('status', $payload)) {
            $activity->setStatus((string) $payload['status']);
        }

        if (array_key_exists('openingSpeech', $payload)) {
            $activity->setOpeningSpeech($payload['openingSpeech'] !== null ? (string) $payload['openingSpeech'] : null);
        }

        if (array_key_exists('description', $payload)) {
            $activity->setDescription($payload['description'] !== null ? (string) $payload['description'] : null);
        }

        if (array_key_exists('illustrationUrl', $payload)) {
            $activity->setIllustrationUrl($this->normalizeMediaUrl($payload['illustrationUrl'] ?? null));
        }

        if (array_key_exists('reminderAt', $payload)) {
            $value = $payload['reminderAt'];
            if ($value === null || $value === '') {
                $activity->setReminderAt(null);
            } else {
                try {
                    $activity->setReminderAt(new \DateTimeImmutable((string) $value));
                } catch (\Throwable) {
                    return new JsonResponse(['error' => 'Format de reminderAt invalide'], JsonResponse::HTTP_BAD_REQUEST);
                }
            }
        }

        if (array_key_exists('registrationEndAt', $payload)) {
            $value = $payload['registrationEndAt'];
            if ($value === null || $value === '') {
                $activity->setRegistrationEndAt(null);
            } else {
                try {
                    $activity->setRegistrationEndAt(new \DateTimeImmutable((string) $value));
                } catch (\Throwable) {
                    return new JsonResponse(['error' => 'Format de registrationEndAt invalide'], JsonResponse::HTTP_BAD_REQUEST);
                }
            }
        }

        if (array_key_exists('universeId', $payload)) {
            $universe = $this->universRepository->find((int) $payload['universeId']);
            if (!$universe instanceof Univers) {
                return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $activity->setUniverse($universe);
        }

        if ($activity->getUniverse() === null) {
            return new JsonResponse(['error' => 'universeId requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('factionId', $payload)) {
            if ($payload['factionId'] === null || (int) $payload['factionId'] === 0) {
                $activity->setFaction(null);
            } else {
                $faction = $this->factionRepository->find((int) $payload['factionId']);
                if (!$faction instanceof Faction) {
                    return new JsonResponse(['error' => 'Faction introuvable'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $activity->setFaction($faction);
            }
        }

        if (is_array($payload['threadIds'] ?? null)) {
            foreach ($activity->getThreads()->toArray() as $thread) {
                $activity->removeThread($thread);
            }

            foreach ($payload['threadIds'] as $threadId) {
                $thread = $this->threadRepository->find((int) $threadId);
                if ($thread instanceof Thread) {
                    $activity->addThread($thread);
                }
            }
        }

        try {
            $this->rulesService->validateActivityConsistency($activity);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return null;
    }

    private function canManageUniverse(User $user, ?Univers $universe): bool
    {
        if (!$universe instanceof Univers) {
            return false;
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            foreach ($user->getAdminUniverses() as $adminUniverse) {
                if ((int) $adminUniverse->getId() === (int) $universe->getId()) {
                    return true;
                }
            }
        }

        // Prévu pour un futur rôle animateur d’univers.
        if (in_array('ROLE_UNIVERSE_ANIMATOR', $user->getRoles(), true)) {
            if (method_exists($user, 'getAnimatorUniverses')) {
                /** @var mixed $animatorUniverses */
                $animatorUniverses = $user->getAnimatorUniverses();
                if (is_iterable($animatorUniverses)) {
                    foreach ($animatorUniverses as $animatorUniverse) {
                        if ((int) $animatorUniverse->getId() === (int) $universe->getId()) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function canEditActivity(User $user, RpActivity $activity): bool
    {
        if ($this->canManageUniverse($user, $activity->getUniverse())) {
            return true;
        }

        return (int) ($activity->getCreatedBy()?->getId() ?? 0) === (int) $user->getId();
    }

    private function canUseEventCharacters(User $user, RpActivity $activity): bool
    {
        if ($this->canEditActivity($user, $activity)) {
            return true;
        }

        foreach ($activity->getAllowedUsers() as $allowedUser) {
            if ((int) $allowedUser->getId() === (int) $user->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Character[]
     */
    private function getSelectableCharactersForActivity(RpActivity $activity, User $user): array
    {
        if (!$activity->getUniverse()) {
            return [];
        }

        $standardCharacters = $this->characterRepository->findValidatedCharactersForUserAndUniverse($user, $activity->getUniverse());

        $eventCharacters = [];
        if ($this->canUseEventCharacters($user, $activity)) {
            $eventCharacters = $this->characterRepository->createQueryBuilder('c')
                ->andWhere('c.eventActivity = :activity')
                ->andWhere('c.kind = :kind')
                ->setParameter('activity', $activity)
                ->setParameter('kind', Character::KIND_EVENT)
                ->orderBy('c.name', 'ASC')
                ->getQuery()
                ->getResult();
        }

        $itemsById = [];
        foreach (array_merge($standardCharacters, $eventCharacters) as $character) {
            $itemsById[(int) $character->getId()] = $character;
        }

        return array_values($itemsById);
    }

    private function serializeEventCharactersContext(RpActivity $activity): array
    {
        $eventCharacters = $this->characterRepository->createQueryBuilder('c')
            ->leftJoin('c.user', 'owner')
            ->addSelect('owner')
            ->andWhere('c.eventActivity = :activity')
            ->andWhere('c.kind = :kind')
            ->setParameter('activity', $activity)
            ->setParameter('kind', Character::KIND_EVENT)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $candidateUsers = [];
        if ($activity->getCreatedBy() instanceof User) {
            $candidateUsers[(int) $activity->getCreatedBy()->getId()] = $activity->getCreatedBy();
        }

        foreach ($activity->getAllowedUsers() as $allowedUser) {
            $candidateUsers[(int) $allowedUser->getId()] = $allowedUser;
        }

        foreach ($activity->getRegistrations() as $registration) {
            $registrationUser = $registration->getCharacter()?->getUser();
            if ($registrationUser instanceof User) {
                $candidateUsers[(int) $registrationUser->getId()] = $registrationUser;
            }
        }

        return [
            'items' => array_map(
                static fn (Character $character): array => [
                    'id' => $character->getId(),
                    'name' => $character->getName(),
                    'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
                    'kind' => $character->getKind(),
                    'owner' => $character->getUser() ? [
                        'id' => $character->getUser()?->getId(),
                        'pseudo' => $character->getUser()?->getPseudo(),
                    ] : null,
                ],
                $eventCharacters
            ),
            'whitelist' => [
                'allowedUsers' => array_map(
                    static fn (User $allowedUser): array => [
                        'id' => $allowedUser->getId(),
                        'pseudo' => $allowedUser->getPseudo(),
                    ],
                    $activity->getAllowedUsers()->toArray()
                ),
                'candidates' => array_map(
                    static fn (User $candidate): array => [
                        'id' => $candidate->getId(),
                        'pseudo' => $candidate->getPseudo(),
                    ],
                    array_values($candidateUsers)
                ),
            ],
        ];
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = $this->slugger->slug($title)->lower()->toString();
        if ($baseSlug === '') {
            $baseSlug = 'rp-activity';
        }

        $candidate = $baseSlug;
        $counter = 2;
        while ($this->rpActivityRepository->findOneBy(['slug' => $candidate])) {
            $candidate = sprintf('%s-%d', $baseSlug, $counter);
            $counter++;
        }

        return $candidate;
    }

    private function generateUniqueThreadSlug(string $title): string
    {
        $baseSlug = $this->slugger->slug($title)->lower()->toString();
        if ($baseSlug === '') {
            $baseSlug = 'thread';
        }

        $candidate = $baseSlug;
        $counter = 2;
        while ($this->threadRepository->findOneBy(['slug' => $candidate])) {
            $candidate = sprintf('%s-%d', $baseSlug, $counter);
            $counter++;
        }

        return $candidate;
    }

    private function serializeActivity(RpActivity $activity, ?User $currentUser, bool $detailed = false): array
    {
        $activeRegistrations = array_values(array_filter(
            $activity->getRegistrations()->toArray(),
            static fn (RpActivityRegistration $registration): bool => $registration->isRegistered()
        ));
        $pendingRegistrations = array_values(array_filter(
            $activity->getRegistrations()->toArray(),
            static fn (RpActivityRegistration $registration): bool => $registration->isPending()
        ));
        $pendingRegistrations = array_values(array_filter(
            $activity->getRegistrations()->toArray(),
            static fn (RpActivityRegistration $registration): bool => $registration->isPending()
        ));

        $userRegistrationEntries = [];
        if ($currentUser) {
            $userRegistrationEntries = $this->registrationRepository->findActiveByActivityAndUser($activity, $currentUser);
        }

        $threads = [];
        foreach ($activity->getThreads() as $thread) {
            $lastPostInfo = $thread->getLastPostInfo();
            $threads[] = [
                'id' => $thread->getId(),
                'slug' => $thread->getSlug(),
                'title' => $thread->getTitle(),
                'type' => $thread->getType(),
                'status' => $thread->getStatus(),
                'lastPost' => $lastPostInfo ? [
                    'id' => $lastPostInfo['id'] ?? null,
                    'threadId' => $lastPostInfo['threadId'] ?? null,
                    'threadSlug' => $lastPostInfo['threadSlug'] ?? null,
                    'threadTitle' => $lastPostInfo['title'] ?? null,
                    'author' => $lastPostInfo['author'] ?? null,
                    'character' => $lastPostInfo['character'] ?? null,
                    'avatar' => $this->s3MediaUrlResolver->resolve($lastPostInfo['avatar'] ?? null),
                    'date' => $lastPostInfo['date'] ?? null,
                ] : null,
                'forum' => [
                    'id' => $thread->getForum()?->getId(),
                    'slug' => $thread->getForum()?->getSlug(),
                    'name' => $thread->getForum()?->getName(),
                    'type' => $thread->getForum()?->getType(),
                ],
            ];
        }

        $payload = [
            'id' => $activity->getId(),
            'kind' => $activity->getKind(),
            'title' => $activity->getTitle(),
            'slug' => $activity->getSlug(),
            'status' => $activity->getStatus(),
            'description' => $activity->getDescription(),
            'openingSpeech' => $activity->getOpeningSpeech(),
            'illustrationUrl' => $this->s3MediaUrlResolver->resolve($activity->getIllustrationUrl()),
            'reminderAt' => $activity->getReminderAt()?->format(\DateTimeInterface::ATOM),
            'registrationEndAt' => $activity->getRegistrationEndAt()?->format(\DateTimeInterface::ATOM),
            'reminderSentAt' => $activity->getReminderSentAt()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $activity->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $activity->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'universe' => [
                'id' => $activity->getUniverse()?->getId(),
                'name' => $activity->getUniverse()?->getName(),
                'slug' => $activity->getUniverse()?->getSlug(),
            ],
            'faction' => $activity->getFaction() ? [
                'id' => $activity->getFaction()?->getId(),
                'name' => $activity->getFaction()?->getName(),
                'slug' => $activity->getFaction()?->getSlug(),
                'icon' => $this->s3MediaUrlResolver->resolve($activity->getFaction()?->getIcon()),
                'logo' => $this->s3MediaUrlResolver->resolve($activity->getFaction()?->getLogo()),
            ] : null,
            'registrationsCount' => count($activeRegistrations),
            'pendingRegistrationsCount' => count($pendingRegistrations),
            'registrationsPreview' => array_map(
                static fn (RpActivityRegistration $registration): array => [
                    'id' => $registration->getId(),
                    'status' => $registration->getStatus(),
                    'character' => [
                        'id' => $registration->getCharacter()?->getId(),
                        'name' => $registration->getCharacter()?->getName(),
                        'avatar' => $this->s3MediaUrlResolver->resolve($registration->getCharacter()?->getAvatar()),
                        'userId' => $registration->getCharacter()?->getUser()?->getId(),
                    ],
                    'registeredAt' => $registration->getRegisteredAt()?->format(\DateTimeInterface::ATOM),
                ],
                array_slice($activeRegistrations, 0, 6)
            ),
            'userRegistration' => [
                'isRegistered' => count(array_filter(
                    $userRegistrationEntries,
                    static fn (RpActivityRegistration $registration): bool => $registration->isRegistered()
                )) > 0,
                'characterIds' => array_map(
                    static fn (RpActivityRegistration $registration): int => (int) $registration->getCharacter()?->getId(),
                    array_filter(
                        $userRegistrationEntries,
                        static fn (RpActivityRegistration $registration): bool => $registration->isRegistered()
                    )
                ),
                'pendingCharacterIds' => array_map(
                    static fn (RpActivityRegistration $registration): int => (int) $registration->getCharacter()?->getId(),
                    array_filter(
                        $userRegistrationEntries,
                        static fn (RpActivityRegistration $registration): bool => $registration->isPending()
                    )
                ),
            ],
            'permissions' => [
                'canEdit' => $currentUser ? $this->canEditActivity($currentUser, $activity) : false,
                'canManageRegistrations' => $currentUser ? $this->canEditActivity($currentUser, $activity) : false,
                'canManageEventCharacters' => $currentUser ? $this->canEditActivity($currentUser, $activity) : false,
                'canUseEventCharacters' => $currentUser ? $this->canUseEventCharacters($currentUser, $activity) : false,
            ],
            'linkedThreads' => $threads,
        ];

        if ($detailed) {
            $payload['registrations'] = array_map(
                static fn (RpActivityRegistration $registration): array => [
                    'id' => $registration->getId(),
                    'status' => $registration->getStatus(),
                    'character' => [
                        'id' => $registration->getCharacter()?->getId(),
                        'name' => $registration->getCharacter()?->getName(),
                        'avatar' => $this->s3MediaUrlResolver->resolve($registration->getCharacter()?->getAvatar()),
                        'userId' => $registration->getCharacter()?->getUser()?->getId(),
                    ],
                    'registeredAt' => $registration->getRegisteredAt()?->format(\DateTimeInterface::ATOM),
                ],
                array_values(array_filter(
                    $activity->getRegistrations()->toArray(),
                    static fn (RpActivityRegistration $registration): bool => in_array(
                        $registration->getStatus(),
                        [RpActivityRegistration::STATUS_PENDING, RpActivityRegistration::STATUS_REGISTERED],
                        true
                    )
                ))
            );
        }

        return $payload;
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

    private function createEventDiscussionThread(RpActivity $activity, User $user): void
    {
        $universe = $activity->getUniverse();
        if (!$universe instanceof Univers) {
            return;
        }

        $discussionForum = $this->ensureEventDiscussionSubforum($universe);
        $threadTitle = $activity->getTitle();

        $thread = new Thread();
        $thread->setTitle($threadTitle);
        $thread->setSlug($this->generateUniqueThreadSlug($threadTitle));
        $thread->setForum($discussionForum);
        $thread->setUniverse($universe);
        $thread->setAuthor($user);
        $thread->setType('hrp');
        $thread->setStatus('open');

        $content = trim((string) ($activity->getOpeningSpeech() ?? ''));
        if ($content === '') {
            $content = trim((string) ($activity->getDescription() ?? ''));
        }
        if ($content === '') {
            $content = sprintf('Discussion ouverte pour l’event "%s".', $activity->getTitle());
        }

        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($user);
        $post->setType('normal');
        $post->setContent($content);

        $activity->addThread($thread);
        $this->entityManager->persist($thread);
        $this->entityManager->persist($post);
    }

    private function ensureEventDiscussionSubforum(Univers $universe): Forum
    {
        $parentForum = $this->findForumByName($universe, self::EVENT_DISCUSSION_PARENT_FORUM_NAME, null);
        if (!$parentForum instanceof Forum) {
            $parentForum = new Forum();
            $parentForum->setName(self::EVENT_DISCUSSION_PARENT_FORUM_NAME);
            $parentForum->setSlug($this->generateUniqueForumSlug('plateforme-joueur', $universe));
            $parentForum->setUniverse($universe);
            $parentForum->setType('player_platform');
            $parentForum->setStatus('open');
            $parentForum->setIsRoleplay(false);
            $parentForum->setPosition($this->getNextForumPosition($universe, null));
            $this->entityManager->persist($parentForum);
        } else {
            $parentForum->setType('player_platform');
        }

        $subforum = $this->findForumByName($universe, self::EVENT_DISCUSSION_SUBFORUM_NAME, $parentForum);
        if ($subforum instanceof Forum) {
            $subforum->setType('player_platform');
            return $subforum;
        }

        $subforum = new Forum();
        $subforum->setName(self::EVENT_DISCUSSION_SUBFORUM_NAME);
        $subforum->setSlug($this->generateUniqueForumSlug(self::EVENT_DISCUSSION_SUBFORUM_NAME, $universe));
        $subforum->setUniverse($universe);
        $subforum->setParent($parentForum);
        $subforum->setType('player_platform');
        $subforum->setStatus('open');
        $subforum->setIsRoleplay(false);
        $subforum->setPosition($this->getNextForumPosition($universe, $parentForum));
        $this->entityManager->persist($subforum);

        return $subforum;
    }

    private function findForumByName(Univers $universe, string $name, ?Forum $parent): ?Forum
    {
        if ($parent instanceof Forum && $parent->getId() === null) {
            // Parent encore non flushé: impossible de requêter avec une entité sans identifiant.
            return null;
        }

        $qb = $this->forumRepository->createQueryBuilder('f')
            ->andWhere('f.universe = :universe')
            ->andWhere('LOWER(f.name) = :name')
            ->setParameter('universe', $universe)
            ->setParameter('name', mb_strtolower($name));

        if ($parent instanceof Forum) {
            $qb->andWhere('f.parent = :parent')->setParameter('parent', $parent);
        } else {
            $qb->andWhere('f.parent IS NULL');
        }

        return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    private function getNextForumPosition(Univers $universe, ?Forum $parent): int
    {
        if ($parent instanceof Forum && $parent->getId() === null) {
            // Nouveau parent non persisté: pas encore de sous-forums en base.
            return 1;
        }

        $qb = $this->forumRepository->createQueryBuilder('f')
            ->select('MAX(f.position)')
            ->andWhere('f.universe = :universe')
            ->setParameter('universe', $universe);

        if ($parent instanceof Forum) {
            $qb->andWhere('f.parent = :parent')->setParameter('parent', $parent);
        } else {
            $qb->andWhere('f.parent IS NULL');
        }

        $maxPosition = $qb->getQuery()->getSingleScalarResult();
        return ((int) ($maxPosition ?? 0)) + 1;
    }

    /**
     * PNJ rattachés à la faction de la mission (univers + statut validé).
     *
     * @return Npc[]
     */
    private function getFactionNpcsForMissionActivity(RpActivity $activity): array
    {
        if ($activity->getKind() !== RpActivity::KIND_MISSION) {
            return [];
        }

        $faction = $activity->getFaction();
        $universe = $activity->getUniverse();
        if (!$faction instanceof Faction || !$universe instanceof Univers) {
            return [];
        }

        return $this->npcRepository->createQueryBuilder('n')
            ->innerJoin('n.factionsRelation', 'fac')
            ->andWhere('fac.id = :factionId')
            ->andWhere('n.status = :validated')
            ->andWhere('n.universe = :universe')
            ->setParameter('factionId', (int) $faction->getId())
            ->setParameter('validated', Npc::STATUS_VALIDATED)
            ->setParameter('universe', $universe)
            ->orderBy('n.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function serializeSelectableNpc(Npc $npc): array
    {
        return [
            'id' => $npc->getId(),
            'name' => $npc->getName(),
            'avatar' => $this->s3MediaUrlResolver->resolve($npc->getAvatar()),
            'entityType' => 'npc',
        ];
    }

    private function resolveFactionNpcForActivity(RpActivity $activity, int $npcId): ?Npc
    {
        if ($npcId <= 0) {
            return null;
        }

        $npc = $this->npcRepository->find($npcId);
        if (!$npc instanceof Npc) {
            return null;
        }

        foreach ($this->getFactionNpcsForMissionActivity($activity) as $candidate) {
            if ((int) $candidate->getId() === $npcId) {
                return $npc;
            }
        }

        return null;
    }

    private function generateUniqueForumSlug(string $name, ?Univers $universe = null): string
    {
        $baseSlug = $this->slugger->slug($name)->lower()->toString();
        if ($baseSlug === '') {
            $baseSlug = 'forum';
        }

        if ($baseSlug === 'plateforme-joueur-pj') {
            $baseSlug = 'plateforme-joueur';
        }

        $universeSlug = trim((string) ($universe?->getSlug() ?? ''));
        if ($universeSlug !== '') {
            $baseSlug = sprintf('%s-%s', $baseSlug, $universeSlug);
        }

        $candidate = $baseSlug;
        $counter = 2;
        while ($this->forumRepository->findOneBy(['slug' => $candidate]) !== null) {
            $candidate = sprintf('%s-%d', $baseSlug, $counter);
            $counter++;
        }

        return $candidate;
    }
}
