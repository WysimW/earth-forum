<?php

namespace App\Service;

use App\Entity\Character;
use App\Entity\Npc;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;

class AuthorDisplayResolver
{
    public const ANONYMOUS_LABEL = 'Anonyme';

    /**
     * @return array{displayName: string, avatar: ?string, userId: ?int, characterId: ?int, characterName: ?string, isAnonymous: bool}
     */
    public function resolveThreadAuthor(Thread $thread): array
    {
        $user = $thread->getAuthor();
        $isRoleplay = $thread->getType() === 'roleplay';

        if ($isRoleplay) {
            $characterCreator = $thread->getCharacterCreator();
            if ($characterCreator instanceof Character) {
                return $this->buildFromCharacter($characterCreator, $user);
            }

            foreach ($thread->getPosts() as $post) {
                if ($post instanceof Post && $post->getCharacter() instanceof Character) {
                    return $this->buildFromCharacter($post->getCharacter(), $user);
                }
            }
        }

        return $this->buildFromUser($user);
    }

    /**
     * @return array{displayName: string, avatar: ?string, userId: ?int, characterId: ?int, characterName: ?string, isAnonymous: bool}
     */
    public function resolvePostAuthor(Post $post, bool $isRoleplay): array
    {
        $user = $post->getAuthor();
        $character = $post->getCharacter();

        if ($isRoleplay && $character instanceof Character) {
            return $this->buildFromCharacter($character, $user);
        }

        if ($isRoleplay && $post->getNpcs()->count() > 0) {
            $npc = $post->getNpcs()->first();
            if ($npc instanceof Npc) {
                return $this->buildFromNpc($npc, $user);
            }
        }

        return $this->buildFromUser($user);
    }

    /**
     * @return array{displayName: string, avatar: ?string, userId: ?int, characterId: ?int, characterName: ?string, isAnonymous: bool}
     */
    public function resolveLastPost(Post $post, Thread $thread): array
    {
        $isRoleplay = $thread->getType() === 'roleplay';
        $resolved = $this->resolvePostAuthor($post, $isRoleplay);

        if ($isRoleplay && $resolved['isAnonymous'] && !$post->getCharacter() && $post->getNpcs()->count() === 0) {
            $characterCreator = $thread->getCharacterCreator();
            if ($characterCreator instanceof Character) {
                return $this->buildFromCharacter($characterCreator, $post->getAuthor());
            }
        }

        return $resolved;
    }

    /**
     * @return array{displayName: string, avatar: ?string, userId: ?int, characterId: ?int, characterName: ?string, isAnonymous: bool}
     */
    private function buildFromUser(?User $user): array
    {
        if (!$user) {
            return [
                'displayName' => self::ANONYMOUS_LABEL,
                'avatar' => null,
                'userId' => null,
                'characterId' => null,
                'characterName' => null,
                'isAnonymous' => true,
            ];
        }

        return [
            'displayName' => $user->getPseudo() ?? self::ANONYMOUS_LABEL,
            'avatar' => $user->getAvatar(),
            'userId' => $user->getId(),
            'characterId' => null,
            'characterName' => null,
            'isAnonymous' => false,
        ];
    }

    /**
     * @return array{displayName: string, avatar: ?string, userId: ?int, characterId: ?int, characterName: ?string, isAnonymous: bool}
     */
    private function buildFromCharacter(Character $character, ?User $user): array
    {
        $displayName = $character->getName() ?: ($user?->getPseudo() ?? self::ANONYMOUS_LABEL);

        return [
            'displayName' => $displayName,
            'avatar' => $character->getAvatar() ?: $user?->getAvatar(),
            'userId' => $user?->getId(),
            'characterId' => $character->getId(),
            'characterName' => $character->getName(),
            'isAnonymous' => $displayName === self::ANONYMOUS_LABEL,
        ];
    }

    /**
     * @return array{displayName: string, avatar: ?string, userId: ?int, characterId: ?int, characterName: ?string, isAnonymous: bool}
     */
    private function buildFromNpc(Npc $npc, ?User $user): array
    {
        $displayName = $npc->getName() ?: ($user?->getPseudo() ?? self::ANONYMOUS_LABEL);

        return [
            'displayName' => $displayName,
            'avatar' => $npc->getAvatar() ?: $user?->getAvatar(),
            'userId' => $user?->getId(),
            'characterId' => $npc->getId(),
            'characterName' => $npc->getName(),
            'isAnonymous' => $displayName === self::ANONYMOUS_LABEL,
        ];
    }
}
