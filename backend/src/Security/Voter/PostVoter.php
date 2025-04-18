<?php

namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class PostVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Post) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Post $post */
        $post = $subject;

        return match($attribute) {
            self::EDIT => $this->canEdit($post, $user),
            self::DELETE => $this->canDelete($post, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canEdit(Post $post, User $user): bool
    {
        // L'auteur du post peut éditer
        if ($post->getAuthor() === $user) {
            return true;
        }

        // Les administrateurs peuvent éditer
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Les modérateurs peuvent éditer
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        return false;
    }

    private function canDelete(Post $post, User $user): bool
    {
        // L'auteur du post peut supprimer
        if ($post->getAuthor() === $user) {
            return true;
        }

        // Les administrateurs peuvent supprimer
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Les modérateurs peuvent supprimer
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        return false;
    }
} 