<?php

namespace App\Service;

use App\Repository\PostRepository;
use App\Repository\ThreadRepository;

class LastPostService
{
    private PostRepository $postRepository;
    private ThreadRepository $threadRepository;

    public function __construct(
        PostRepository $postRepository,
        ThreadRepository $threadRepository,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
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
        
        // Vérifier si le thread du dernier post est un thread RP
        $isThreadRoleplay = $thread->getType() === 'roleplay';
        
        // Pour les threads RP, utiliser les informations du personnage du dernier post si disponible
        $avatar = null;
        $displayName = null;
        
        if ($isThreadRoleplay) {
            // Thread RP : prioriser le personnage du dernier post
            if ($character) {
                $avatar = $character->getAvatar() ?: ($author ? $author->getAvatar() : null);
                $displayName = $character->getName();
            } else {
                // Si pas de personnage dans le post, essayer avec le characterCreator du thread
                $threadCharacterCreator = $thread->getCharacterCreator();
                if ($threadCharacterCreator) {
                    $avatar = $threadCharacterCreator->getAvatar() ?: ($author ? $author->getAvatar() : null);
                    $displayName = $threadCharacterCreator->getName();
                } else {
                    // Fallback sur l'utilisateur
                    $avatar = $author ? $author->getAvatar() : null;
                    $displayName = $author ? $author->getPseudo() : 'Anonyme';
                }
            }
        } else {
            // Thread non-RP : utiliser l'utilisateur
            $avatar = $author ? $author->getAvatar() : null;
            $displayName = $author ? $author->getPseudo() : 'Anonyme';
        }

        return [
            'postId' => $lastPost->getId(),
            'threadId' => $thread->getId(),
            'threadSlug' => $thread->getSlug(),
            'threadTitle' => $thread->getTitle(),
            'date' => $lastPost->getCreatedAt(),
            'author' => $displayName, // Nom du personnage pour les threads RP, sinon pseudo utilisateur
            'authorId' => $author ? $author->getId() : null, // ID de l'utilisateur pour le filtrage
            'character' => $character ? $character->getName() : null,
            'avatar' => $this->s3MediaUrlResolver->resolve($avatar),
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

        $thread = $lastPost->getThread();
        $author = $lastPost->getAuthor();
        $character = $lastPost->getCharacter();
        
        // Vérifier si le thread du dernier post est un thread RP
        $isThreadRoleplay = $thread->getType() === 'roleplay';
        
        // Pour les threads RP, utiliser les informations du personnage du dernier post si disponible
        $avatar = null;
        $displayName = null;
        
        if ($isThreadRoleplay) {
            // Thread RP : prioriser le personnage du dernier post
            if ($character) {
                $avatar = $character->getAvatar() ?: ($author ? $author->getAvatar() : null);
                $displayName = $character->getName();
            } else {
                // Si pas de personnage dans le post, essayer avec le characterCreator du thread
                $threadCharacterCreator = $thread->getCharacterCreator();
                if ($threadCharacterCreator) {
                    $avatar = $threadCharacterCreator->getAvatar() ?: ($author ? $author->getAvatar() : null);
                    $displayName = $threadCharacterCreator->getName();
                } else {
                    // Fallback sur l'utilisateur
                    $avatar = $author ? $author->getAvatar() : null;
                    $displayName = $author ? $author->getPseudo() : 'Anonyme';
                }
            }
        } else {
            // Thread non-RP : utiliser l'utilisateur
            $avatar = $author ? $author->getAvatar() : null;
            $displayName = $author ? $author->getPseudo() : 'Anonyme';
        }

        return [
            'postId' => $lastPost->getId(),
            'threadId' => $thread->getId(),
            'threadSlug' => $thread->getSlug(),
            'threadTitle' => $thread->getTitle(),
            'date' => $lastPost->getCreatedAt(),
            'author' => $displayName, // Nom du personnage pour les threads RP, sinon pseudo utilisateur
            'authorId' => $author ? $author->getId() : null, // ID de l'utilisateur pour le filtrage
            'character' => $character ? $character->getName() : null,
            'avatar' => $this->s3MediaUrlResolver->resolve($avatar),
        ];
    }
}