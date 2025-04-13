<?php

namespace App\Service;

use App\Repository\PostRepository;
use App\Repository\ThreadRepository;

class LastPostService
{
    private PostRepository $postRepository;
    private ThreadRepository $threadRepository;

    public function __construct(PostRepository $postRepository, ThreadRepository $threadRepository)
    {
        $this->postRepository = $postRepository;
        $this->threadRepository = $threadRepository;
    }

    /**
     * Récupère les informations du dernier post pour un forum spécifique
     * en incluant les posts des sous-forums
     */
    public function getLastPostInfoForForum(int $forumId): ?array
    {
        // Récupérer le dernier post du forum
        $lastPost = $this->postRepository->findLastPostInForum($forumId);

        if (!$lastPost) {
            return null;
        }

        $thread = $lastPost->getThread();
        $author = $lastPost->getAuthor();
        $character = $lastPost->getCharacter();

        return [
            'postId' => $lastPost->getId(),
            'threadId' => $thread->getId(),
            'threadTitle' => $thread->getTitle(),
            'date' => $lastPost->getCreatedAt(),
            'author' => $author ? $author->getPseudo() : 'Anonyme',
            'character' => $character ? $character->getName() : null,
        ];
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

        $author = $lastPost->getAuthor();
        $character = $lastPost->getCharacter();

        return [
            'postId' => $lastPost->getId(),
            'date' => $lastPost->getCreatedAt(),
            'author' => $author ? $author->getPseudo() : 'Anonyme',
            'character' => $character ? $character->getName() : null,
        ];
    }
}