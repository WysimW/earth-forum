<?php

namespace App\Security\Voter;

use App\Entity\Npc;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class NpcVoter extends Voter
{
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Npc) {
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

        /** @var Npc $npc */
        $npc = $subject;

        return match($attribute) {
            self::EDIT => $this->canEdit($npc, $user),
            self::DELETE => $this->canDelete($npc, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canEdit(Npc $npc, User $user): bool
    {
        // Le propriétaire peut éditer
        if ($npc->getUser() === $user) {
            return true;
        }

        // Les modérateurs peuvent éditer
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        return false;
    }

    private function canDelete(Npc $npc, User $user): bool
    {
        // Le propriétaire peut supprimer
        if ($npc->getUser() === $user) {
            return true;
        }

        // Les modérateurs peuvent supprimer
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        return false;
    }
} 