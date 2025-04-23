<?php

namespace App\Controller\Messaging;

use App\Entity\User;
use App\Repository\Messaging\ConversationRepository;
use App\Repository\Messaging\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messaging/unread')]
#[IsGranted('ROLE_USER')]
class UnreadController extends AbstractController
{
    public function __construct(
        private MessageRepository $messageRepository,
        private ConversationRepository $conversationRepository
    ) {
    }

    #[Route('/count', name: 'app_messaging_unread_count', methods: ['GET'])]
    public function count(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Compter les messages non lus dans toutes les conversations de l'utilisateur
        $total = $this->messageRepository->countAllUnread($user);
        
        // Récupérer les conversations de l'utilisateur
        $conversations = $this->conversationRepository->findByUser($user);
        
        // Créer un tableau associatif avec le nombre de messages non lus par conversation
        $conversationsUnread = [];
        foreach ($conversations as $conversation) {
            $unreadCount = $this->messageRepository->countUnreadByConversation($conversation, $user);
            if ($unreadCount > 0) {
                $conversationsUnread[$conversation->getId()] = $unreadCount;
            }
        }
        
        return new JsonResponse([
            'success' => true,
            'total' => $total,
            'conversations' => $conversationsUnread
        ]);
    }
} 