<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\ReadPostRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UnreadMessagesExtension extends AbstractExtension
{
    private ReadPostRepository $readPostRepository;
    private Security $security;

    public function __construct(ReadPostRepository $readPostRepository, Security $security)
    {
        $this->readPostRepository = $readPostRepository;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('unread_messages_count', [$this, 'getUnreadMessagesCount']),
            new TwigFunction('unread_participating_count', [$this, 'getUnreadParticipatingCount']),
            new TwigFunction('unread_admin_count', [$this, 'getUnreadAdminCount']),
            new TwigFunction('total_unread_count', [$this, 'getTotalUnreadCount']),
        ];
    }

    /**
     * Retourne le nombre de messages standards non lus
     */
    public function getUnreadMessagesCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        return count($this->readPostRepository->findUnreadPostsForUser($user));
    }

    /**
     * Retourne le nombre de messages non lus dans les threads où l'utilisateur participe
     */
    public function getUnreadParticipatingCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        return count($this->readPostRepository->findUnreadPostsInParticipatingThreads($user));
    }

    /**
     * Retourne le nombre de messages non lus de l'administration
     */
    public function getUnreadAdminCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        return count($this->readPostRepository->findUnreadAdminPostsForUser($user));
    }

    /**
     * Retourne le nombre total de messages non lus
     */
    public function getTotalUnreadCount(): int
    {
        return $this->getUnreadMessagesCount() 
             + $this->getUnreadParticipatingCount() 
             + $this->getUnreadAdminCount();
    }
} 