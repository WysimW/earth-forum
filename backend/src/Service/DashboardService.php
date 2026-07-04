<?php

namespace App\Service;

use App\Entity\Character;
use App\Entity\Faction;
use App\Entity\Thread;
use App\Entity\User;
use App\Repository\CharacterRepository;
use App\Repository\FactionRepository;
use App\Repository\Messaging\MessageRepository;
use App\Repository\PostRepository;
use App\Repository\ThreadRepository;
use App\Repository\UniversRepository;

class DashboardService
{
    private const ALLOWED_THREAD_TYPES = ['all', 'roleplay', 'hrp'];
    private const ALLOWED_THREAD_STATUSES = ['all', 'open', 'closed', 'archived'];

    public function __construct(
        private ThreadRepository $threadRepository,
        private CharacterRepository $characterRepository,
        private FactionRepository $factionRepository,
        private PostRepository $postRepository,
        private MessageRepository $messageRepository,
        private UniversRepository $universRepository,
        private S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    public function buildDashboard(User $user, array $options = []): array
    {
        $threadType = $this->normalizeThreadType($options['threadType'] ?? 'all');
        $threadStatus = $this->normalizeThreadStatus($options['threadStatus'] ?? 'all');
        $limit = max(1, min(20, (int) ($options['limit'] ?? 5)));
        $universeId = $this->resolveUniverseId($options['universe'] ?? null);

        $characters = $this->characterRepository->findCharactersByUser($user);
        if ($universeId !== null) {
            $characters = array_values(array_filter(
                $characters,
                static fn (Character $character): bool => self::resolveCharacterUniverseId($character) === $universeId
            ));
        }

        $userFactions = $this->collectUserFactions($user, $characters);
        if ($universeId !== null) {
            $userFactions = array_values(array_filter(
                $userFactions,
                static fn (Faction $faction): bool => $faction->getUniverse()?->getId() === $universeId
            ));
        }

        $statusFilter = $threadStatus === 'all' ? null : $threadStatus;
        $typeFilter = $threadType === 'all' ? null : $threadType;

        $participatingThreads = $this->threadRepository->findThreadsWithUserParticipationAllTypes(
            $user->getId(),
            $typeFilter,
            $statusFilter,
            $universeId,
            $limit
        );

        $createdThreads = $this->threadRepository->findThreadsCreatedByUser(
            $user->getId(),
            $typeFilter,
            $statusFilter,
            $universeId,
            $limit
        );

        $recentThreads = $universeId !== null
            ? $this->threadRepository->findRecentThreadsByUniverse($universeId, 'open', $limit)
            : $this->threadRepository->findRecentActiveThreads($limit);

        return [
            'stats' => [
                'characters' => count($characters),
                'factions' => count($userFactions),
                'threadsCreated' => $this->threadRepository->countThreadsCreatedByUser($user->getId(), $universeId),
                'threadsParticipating' => $this->threadRepository->countThreadsParticipatingByUser($user->getId(), $universeId),
                'posts' => $this->postRepository->countByAuthor($user),
                'unreadMessages' => $this->messageRepository->countAllUnread($user),
            ],
            'characters' => array_map(
                fn (Character $character): array => $this->serializeCharacter($character),
                array_slice($characters, 0, $limit)
            ),
            'participatingThreads' => array_map(
                fn (Thread $thread): array => $this->serializeThread($thread),
                $participatingThreads
            ),
            'createdThreads' => array_map(
                fn (Thread $thread): array => $this->serializeThread($thread),
                $createdThreads
            ),
            'recentThreads' => array_map(
                fn (Thread $thread): array => $this->serializeThread($thread),
                $recentThreads
            ),
            'factions' => array_map(
                fn (Faction $faction): array => $this->serializeFaction($faction, $user),
                array_slice($userFactions, 0, $limit)
            ),
            'permissions' => [
                'canCreateFaction' => $user->canCreateFaction(),
            ],
        ];
    }

    /**
     * @param Character[] $characters
     *
     * @return Faction[]
     */
    private function collectUserFactions(User $user, array $characters): array
    {
        $factionsById = [];

        foreach ($characters as $character) {
            foreach ($character->getFactionsRelation() as $faction) {
                $factionsById[$faction->getId()] = $faction;
            }
        }

        $ownedFactions = $this->factionRepository->findBy(['founder' => $user]);
        foreach ($ownedFactions as $faction) {
            $factionsById[$faction->getId()] = $faction;
        }

        $factions = array_values($factionsById);
        usort($factions, static fn (Faction $a, Faction $b): int => strcmp($a->getName(), $b->getName()));

        return $factions;
    }

    private function resolveUniverseId(?string $universeSlug): ?int
    {
        if (!is_string($universeSlug) || trim($universeSlug) === '' || $universeSlug === 'portal') {
            return null;
        }

        $universe = $this->universRepository->findOneBy(['slug' => $universeSlug]);

        return $universe?->getId();
    }

    private function normalizeThreadType(?string $threadType): string
    {
        $threadType = is_string($threadType) ? strtolower(trim($threadType)) : 'all';

        return in_array($threadType, self::ALLOWED_THREAD_TYPES, true) ? $threadType : 'all';
    }

    private function normalizeThreadStatus(?string $threadStatus): string
    {
        $threadStatus = is_string($threadStatus) ? strtolower(trim($threadStatus)) : 'all';

        return in_array($threadStatus, self::ALLOWED_THREAD_STATUSES, true) ? $threadStatus : 'all';
    }

    private function serializeCharacter(Character $character): array
    {
        return [
            'id' => $character->getId(),
            'name' => $character->getName(),
            'status' => $character->getStatus(),
            'statusMessage' => $character->getStatusMessage(),
            'avatar' => $this->s3MediaUrlResolver->resolve($character->getAvatar()),
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
    }

    private function serializeThread(Thread $thread): array
    {
        $forum = $thread->getForum();
        $universe = $thread->getUniverse() ?? $forum?->getUniverse();
        if ($universe === null && $forum?->getElseworld()?->getParentUniverse() !== null) {
            $universe = $forum->getElseworld()->getParentUniverse();
        }

        $author = $thread->getAuthor();

        return [
            'id' => $thread->getId(),
            'slug' => $thread->getSlug(),
            'title' => $thread->getTitle(),
            'type' => $thread->getType(),
            'isRoleplay' => $thread->getType() === 'roleplay',
            'status' => $thread->getStatus(),
            'updatedAt' => $thread->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'postCount' => $thread->getPosts()->count(),
            'forum' => $forum ? [
                'id' => $forum->getId(),
                'name' => $forum->getName(),
                'slug' => $forum->getSlug(),
            ] : null,
            'universe' => $universe ? [
                'id' => $universe->getId(),
                'name' => $universe->getName(),
                'slug' => $universe->getSlug(),
            ] : null,
            'author' => $author ? [
                'id' => $author->getId(),
                'pseudo' => $author->getPseudo(),
                'avatar' => $this->s3MediaUrlResolver->resolve($author->getAvatar()),
            ] : null,
        ];
    }

    private function serializeFaction(Faction $faction, User $user): array
    {
        $isOwner = $faction->getFounder()?->getId() === $user->getId();
        $hasMyCharacter = false;

        foreach ($faction->getCharacters() as $character) {
            if ($character->getUser()?->getId() === $user->getId()) {
                $hasMyCharacter = true;
                break;
            }
        }

        $myCharacters = [];
        foreach ($faction->getCharacters() as $character) {
            if ($character->getUser()?->getId() === $user->getId()) {
                $myCharacters[] = $character->getName();
            }
        }

        return [
            'id' => $faction->getId(),
            'name' => $faction->getName(),
            'slug' => $faction->getSlug(),
            'alignment' => $faction->getAlignment(),
            'status' => $faction->getStatus(),
            'icon' => $this->s3MediaUrlResolver->resolve($faction->getIcon()),
            'logo' => $this->s3MediaUrlResolver->resolve($faction->getLogo()),
            'universe' => $faction->getUniverse() ? [
                'id' => $faction->getUniverse()->getId(),
                'name' => $faction->getUniverse()->getName(),
                'slug' => $faction->getUniverse()->getSlug(),
            ] : null,
            'founder' => $faction->getFounder() ? [
                'id' => $faction->getFounder()->getId(),
                'pseudo' => $faction->getFounder()->getPseudo(),
            ] : null,
            'isOwner' => $isOwner,
            'isMine' => $isOwner || $hasMyCharacter,
            'myCharacters' => $myCharacters,
            'membersCount' => [
                'characters' => $faction->getCharacters()->count(),
                'npcs' => $faction->getNpcs()->count(),
            ],
        ];
    }

    private static function resolveCharacterUniverseId(Character $character): ?int
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
