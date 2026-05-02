<?php

// src/Controller/ThreadController.php

namespace App\Controller\Api;

use App\Entity\Character;
use App\Entity\Faction;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Forum;
use App\Entity\Thread;
use App\Repository\PostRepository;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ThreadController extends AbstractController
{
    public function __construct(
        private ThreadRepository $threadRepository,
        private PostRepository $postRepository
    ) {
    }

    #[Route('/api/threads/{slug}', name: 'get_thread_detail', methods: ['GET'])]
    public function getThreadDetail(string $slug, Request $request): JsonResponse
    {
        $thread = $this->resolveThreadBySlugOrId($slug);

        if (!$thread) {
            return new JsonResponse(['error' => 'Thread non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 20));
        $postsPage = $this->postRepository->findPaginatedByThread($thread->getId(), $page, $limit);
        $isRoleplay = $thread->getType() === 'roleplay';
        $posts = array_map(fn (Post $post): array => $this->serializePost($post, $isRoleplay), $postsPage['posts']);

        $character = $thread->getCharacterSheet();
        $isCharacterSheet = $thread->getType() === 'character_sheet' || $character !== null;

        $breadcrumb = $this->buildThreadBreadcrumb($thread);

        $participants = array_map(
            fn (Character $participant): array => $this->serializeCharacterParticipant($participant),
            $thread->getParticipants()->toArray()
        );
        $threadFactions = array_map(
            fn (Faction $faction): array => [
                'id' => $faction->getId(),
                'name' => $faction->getName(),
                'icon' => $faction->getIcon(),
                'logo' => $faction->getLogo(),
            ],
            $thread->getFactions()->toArray()
        );

        $allowedActors = $this->buildAllowedActors($thread);

        $data = [
            'threadId' => $thread->getId(),
            'slug' => $thread->getSlug(),
            'title' => $thread->getTitle(),
            'author' => $thread->getAuthor()->getPseudo(),
            'authorId' => $thread->getAuthor()->getId(),
            'authorAvatar' => $thread->getAuthor()->getAvatar(),
            'date' => $thread->getCreatedAt()->format('Y-m-d H:i:s'),
            'createdAt' => $thread->getCreatedAt()->format('Y-m-d H:i:s'),
            'type' => $thread->getType(),
            'isRoleplay' => $isRoleplay,
            'status' => $thread->getStatus(),
            'universe' => $thread->getUniverse() ? [
                'id' => $thread->getUniverse()->getId(),
                'name' => $thread->getUniverse()->getName(),
                'slug' => $thread->getUniverse()->getSlug(),
            ] : ($thread->getForum()->getUniverse() ? [
                'id' => $thread->getForum()->getUniverse()->getId(),
                'name' => $thread->getForum()->getUniverse()->getName(),
                'slug' => $thread->getForum()->getUniverse()->getSlug(),
            ] : null),
            'maxParticipants' => $thread->getMaxParticipants(),
            'isFull' => $thread->isFull(),
            'participants' => $participants,
            'factions' => $threadFactions,
            'forum' => [
                'id' => $thread->getForum()->getId(),
                'name' => $thread->getForum()->getName(),
                'slug' => $thread->getForum()->getSlug(),
            ],
            'breadcrumb' => $breadcrumb,
            'posts' => $posts,
            'postsPagination' => [
                'page' => $postsPage['page'],
                'limit' => $postsPage['limit'],
                'totalPosts' => $postsPage['total'],
                'totalPages' => $postsPage['totalPages'],
                'hasNext' => $postsPage['page'] < $postsPage['totalPages'],
            ],
            'allowedActors' => $allowedActors,
            'isCharacterSheet' => $isCharacterSheet,
            'character' => $character ? [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'firstName' => $character->getFirstName(),
                'lastName' => $character->getLastName(),
                'avatar' => $character->getAvatar(),
                'biography' => $character->getBiography(),
                'personality' => $character->getPersonality(),
                'appearance' => $character->getAppearance(),
                'abilities' => $character->getAbilities(),
                'equipment' => $character->getEquipment(),
                'weaknesses' => $character->getWeaknesses(),
                'age' => $character->getAge(),
                'gender' => $character->getGender(),
                'sexualOrientation' => $character->getSexualOrientation(),
                'civilStatus' => $character->getCivilStatus(),
                'occupation' => $character->getOccupation(),
                'moralAffiliation' => $character->getMoralAffiliation(),
                'factions' => $character->getFactions(),
                'pseudonyms' => $character->getPseudonyms(),
                'actualPseudo' => $character->getActualPseudo(),
                'alias' => $character->getAlias(),
                'sheetTheme' => $character->getSheetTheme(),
                'status' => $character->getStatus(),
                'statusMessage' => $character->getStatusMessage(),
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
            ] : null,
        ];

        return new JsonResponse($data);
    }

    #[Route('/api/threads', name: 'create_thread', methods: ['POST'])]
    public function createThread(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['forum_id'], $data['author_id'], $data['title'], $data['content'])) {
            return new JsonResponse(['error' => 'Payload invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $forum = $em->getRepository(Forum::class)->find((int) $data['forum_id']);
        if (!$forum) {
            return new JsonResponse(['status' => 'Forum not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        if ($forum->getStatus() !== 'open') {
            return new JsonResponse([
                'error' => $forum->getStatus() === 'archived'
                    ? 'Ce forum est archivé'
                    : 'Ce forum est fermé, vous ne pouvez pas créer de thread'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $author = $em->getRepository(User::class)->find((int) $data['author_id']);
        if (!$author) {
            return new JsonResponse(['status' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $threadType = $data['type'] ?? ($forum->getType() === 'roleplay' ? 'roleplay' : 'hrp');
        $isRoleplay = $threadType === 'roleplay';

        $thread = new Thread();
        $thread->setTitle((string) $data['title']);
        $thread->setForum($forum);
        $thread->setAuthor($author);
        $thread->setType($threadType);
        $thread->setSlug($this->generateSlug((string) $data['title']));

        $participantIds = is_array($data['participantCharacterIds'] ?? null) ? $data['participantCharacterIds'] : [];
        foreach ($participantIds as $participantId) {
            $participant = $em->getRepository(Character::class)->find((int) $participantId);
            if ($participant) {
                $thread->addParticipant($participant);
            }
        }

        $factionIds = is_array($data['factionIds'] ?? null) ? $data['factionIds'] : [];
        foreach ($factionIds as $factionId) {
            $faction = $em->getRepository(Faction::class)->find((int) $factionId);
            if ($faction) {
                $thread->addFaction($faction);
            }
        }

        $postCharacter = null;
        if ($isRoleplay) {
            $characterId = isset($data['characterId']) ? (int) $data['characterId'] : 0;
            if ($characterId <= 0) {
                return new JsonResponse(['error' => 'characterId requis pour un thread roleplay'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $postCharacter = $em->getRepository(Character::class)->find($characterId);
            if (!$postCharacter || !$postCharacter->getUser() || $postCharacter->getUser()->getId() !== $author->getId()) {
                return new JsonResponse(['error' => 'Personnage invalide pour cet utilisateur'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $thread->setCharacterCreator($postCharacter);
            $thread->addParticipant($postCharacter);
        }

        $post = new Post();
        $post->setContent((string) $data['content']);
        $post->setThread($thread);
        $post->setAuthor($author);
        $post->setType($isRoleplay ? 'roleplay' : 'normal');
        if ($postCharacter) {
            $post->setCharacter($postCharacter);
        }

        $em->persist($thread);
        $em->persist($post);
        $em->flush();

        return new JsonResponse(['status' => 'Thread created', 'threadId' => $thread->getId(), 'slug' => $thread->getSlug()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/threads/{id}/participants', name: 'api_thread_update_participants', methods: ['PUT'])]
    public function updateParticipants(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $securityUser = $this->getUser();
        if (!$securityUser instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $thread = $this->threadRepository->find($id);
        if (!$thread) {
            return new JsonResponse(['error' => 'Thread non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $isOwner = $thread->getAuthor()?->getId() === $securityUser->getId();
        $canModerate = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MODERATOR');
        if (!$isOwner && !$canModerate) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !is_array($data['participantCharacterIds'] ?? null)) {
            return new JsonResponse(['error' => 'participantCharacterIds requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        foreach ($thread->getParticipants()->toArray() as $participant) {
            $thread->removeParticipant($participant);
        }

        foreach ($data['participantCharacterIds'] as $characterId) {
            $character = $em->getRepository(Character::class)->find((int) $characterId);
            if ($character) {
                $thread->addParticipant($character);
            }
        }

        $em->flush();

        return new JsonResponse(['status' => 'Participants mis à jour']);
    }

    private function resolveThreadBySlugOrId(string $slug): ?Thread
    {
        if (is_numeric($slug)) {
            return $this->threadRepository->find((int) $slug);
        }

        return $this->threadRepository->findOneBy(['slug' => $slug]);
    }

    private function serializePost(Post $post, bool $isRoleplay): array
    {
        $postAuthor = $post->getAuthor();
        $postCharacter = $post->getCharacter();

        $author = $postAuthor ? $postAuthor->getPseudo() : 'Anonyme';
        $avatar = $postAuthor ? $postAuthor->getAvatar() : null;
        $character = null;

        if ($isRoleplay && $postCharacter) {
            $factions = [];
            $factionsRelation = $postCharacter->getFactionsRelation();
            if ($factionsRelation) {
                foreach ($factionsRelation as $faction) {
                    if ($faction && $faction->getName()) {
                        $factions[] = $faction->getName();
                    }
                }
            }

            $character = [
                'id' => $postCharacter->getId(),
                'name' => $postCharacter->getName() ?: $author,
                'firstName' => $postCharacter->getFirstName(),
                'lastName' => $postCharacter->getLastName(),
                'actualPseudo' => $postCharacter->getActualPseudo(),
                'alias' => $postCharacter->getAlias(),
                'avatar' => $postCharacter->getAvatar() ?: ($postAuthor ? $postAuthor->getAvatar() : null),
                'moralAlignment' => $postCharacter->getMoralAffiliation(),
                'occupation' => $postCharacter->getOccupation(),
                'age' => $postCharacter->getAge(),
                'gender' => $postCharacter->getGender(),
                'universe' => $postCharacter->getUniverse() ? [
                    'id' => $postCharacter->getUniverse()->getId(),
                    'name' => $postCharacter->getUniverse()->getName(),
                    'slug' => $postCharacter->getUniverse()->getSlug(),
                ] : null,
                'elseworld' => $postCharacter->getElseworld() ? [
                    'id' => $postCharacter->getElseworld()->getId(),
                    'name' => $postCharacter->getElseworld()->getName(),
                    'slug' => $postCharacter->getElseworld()->getSlug(),
                ] : null,
                'factions' => $factions,
            ];
            $author = $postCharacter->getName() ?: $author;
            $avatar = $postCharacter->getAvatar() ?: ($postAuthor ? $postAuthor->getAvatar() : null);
        }

        return [
            'postId' => $post->getId(),
            'author' => $author,
            'authorId' => $postAuthor ? $postAuthor->getId() : null,
            'avatar' => $avatar,
            'date' => $post->getCreatedAt() ? $post->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'createdAt' => $post->getCreatedAt() ? $post->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'content' => $post->getContent(),
            'type' => $post->getType(),
            'character' => $character,
            'quotedPostId' => $post->getQuotedPost()?->getId(),
        ];
    }

    private function serializeCharacterParticipant(Character $participant): array
    {
        return [
            'id' => $participant->getId(),
            'name' => $participant->getName(),
            'alias' => $participant->getAlias(),
            'avatar' => $participant->getAvatar(),
            'userId' => $participant->getUser()?->getId(),
        ];
    }

    private function buildAllowedActors(Thread $thread): array
    {
        $characters = [];
        foreach ($thread->getParticipants() as $participant) {
            $characters[] = [
                'id' => $participant->getId(),
                'name' => $participant->getName(),
                'avatar' => $participant->getAvatar(),
            ];
        }

        return [
            'mode' => $thread->isRoleplay() ? 'character' : 'user',
            'characters' => $characters,
        ];
    }

    private function buildThreadBreadcrumb(Thread $thread): array
    {
        $forumChain = [];
        $currentForum = $thread->getForum();

        while ($currentForum !== null) {
            $forumChain[] = $currentForum;
            $currentForum = $currentForum->getParent();
        }

        $forumChain = array_reverse($forumChain);

        $breadcrumb = [
            ['name' => 'FORUMS', 'url' => '/forums'],
        ];

        foreach ($forumChain as $forum) {
            $breadcrumb[] = [
                'name' => $forum->getName(),
                'url' => $forum->getSlug() ? "/forums/{$forum->getSlug()}" : null,
            ];
        }

        $breadcrumb[] = [
            'name' => $thread->getTitle(),
            'url' => null,
        ];

        return $breadcrumb;
    }

    private function generateSlug(string $title): string
    {
        $normalized = trim(mb_strtolower($title));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $normalized) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'thread';
        }

        $candidate = $slug;
        $counter = 2;
        while ($this->threadRepository->findOneBy(['slug' => $candidate])) {
            $candidate = sprintf('%s-%d', $slug, $counter);
            $counter++;
        }

        return $candidate;
    }
    
}
