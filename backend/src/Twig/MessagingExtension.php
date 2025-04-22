<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\Messaging\MessageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MessagingExtension extends AbstractExtension
{
    private MessageRepository $messageRepository;
    private Security $security;

    public function __construct(MessageRepository $messageRepository, Security $security)
    {
        $this->messageRepository = $messageRepository;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('messaging_unread_count', [$this, 'getMessagingUnreadCount']),
        ];
    }

    /**
     * Retourne le nombre de messages non lus dans la messagerie
     */
    public function getMessagingUnreadCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        return $this->messageRepository->countAllUnread($user);
    }
} 