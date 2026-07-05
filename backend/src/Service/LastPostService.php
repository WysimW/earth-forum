<?php

namespace App\Service;

use App\Repository\PostRepository;
use App\Repository\ThreadRepository;

class LastPostService
{
    public function __construct(
        private PostRepository $postRepository,
        private ThreadRepository $threadRepository,
        private AuthorDisplayResolver $authorDisplayResolver,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    /**
     * Récupère les informations du dernier post pour un forum spécifique
     * en incluant les posts des sous-forums
     */
    public function getLastPostInfoForForum(int $forumId): ?array
    {
        $lastPost = $this->postRepository->findLastPostInForum($forumId);

        if (!$lastPost) {
            return null;
        }

        return $this->buildLastPostPayload($lastPost, $lastPost->getThread());
    }

    /**
     * Récupère les informations du dernier post pour un thread spécifique
     */
    public function getLastPostInfoForThread(int $threadId): ?array
    {
        $lastPost = $this->postRepository->findLastPostForThread($threadId);

        if (!$lastPost) {
            return null;
        }

        return $this->buildLastPostPayload($lastPost, $lastPost->getThread());
    }

    private function buildLastPostPayload($lastPost, $thread): array
    {
        $resolved = $this->authorDisplayResolver->resolveLastPost($lastPost, $thread);
        $character = $lastPost->getCharacter();

        return [
            'postId' => $lastPost->getId(),
            'threadId' => $thread->getId(),
            'threadSlug' => $thread->getSlug(),
            'threadTitle' => $thread->getTitle(),
            'date' => $lastPost->getCreatedAt(),
            'author' => $resolved['displayName'],
            'authorId' => $resolved['userId'],
            'character' => $character ? $character->getName() : $resolved['characterName'],
            'avatar' => $this->s3MediaUrlResolver->resolve($resolved['avatar']),
        ];
    }
}
