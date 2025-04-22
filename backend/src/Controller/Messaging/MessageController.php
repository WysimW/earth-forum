<?php

namespace App\Controller\Messaging;

use App\Entity\Character;
use App\Entity\Messaging\Conversation;
use App\Entity\Messaging\Message;
use App\Entity\User;
use App\Form\Messaging\MessageFormType;
use App\Repository\CharacterRepository;
use App\Repository\Messaging\ConversationParticipantRepository;
use App\Repository\Messaging\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/messaging')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private ConversationParticipantRepository $participantRepository,
        private CharacterRepository $characterRepository
    ) {
    }

    #[Route('/conversations/{id}/messages', name: 'app_messaging_messages_create', methods: ['POST'])]
    public function create(Request $request, Conversation $conversation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est participant à cette conversation
        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$participant || !$participant->isActive() || !$participant->canWrite()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit d\'écrire dans cette conversation.');
        }
        
        $message = new Message();
        $message->setAuthor($user);
        $message->setConversation($conversation);
        
        $form = $this->createForm(MessageFormType::class, $message);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du personnage si mode RP
            if ($form->has('character') && $form->get('character')->getData()) {
                $characterId = $form->get('character')->getData();
                $character = $this->characterRepository->find($characterId);
                
                if ($character && $character->getUser() === $user) {
                    $message->setCharacter($character);
                    $message->setIsRoleplay(true);
                }
            }
            
            // Mise à jour de la date de la conversation
            $conversation->setUpdatedAt(new \DateTime());
            
            $this->entityManager->persist($message);
            $this->entityManager->flush();
            
            // Mise à jour de la dernière lecture pour l'expéditeur
            $participant->setLastReadAt(new \DateTime());
            $this->entityManager->flush();
            
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                // Pour les requêtes Turbo, renvoyer un stream
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                
                return $this->render('messaging/message/stream.html.twig', [
                    'message' => $message,
                    'conversation' => $conversation
                ]);
            }
            
            // Pour les requêtes classiques, rediriger
            return $this->redirectToRoute('app_messaging_conversation_show', [
                'id' => $conversation->getId()
            ]);
        }
        
        // En cas d'erreur de formulaire
        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            
            return $this->render('messaging/message/form_error.html.twig', [
                'form' => $form,
                'conversation' => $conversation
            ], new Response(null, 422));
        }
        
        // Redirection en cas d'erreur pour les requêtes standard
        $this->addFlash('error', 'Erreur lors de l\'envoi du message.');
        
        return $this->redirectToRoute('app_messaging_conversation_show', [
            'id' => $conversation->getId()
        ]);
    }

    #[Route('/messages/{id}/edit', name: 'app_messaging_message_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Message $message): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est l'auteur du message
        if ($message->getAuthor() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce message.');
        }
        
        // Vérifier si le message est supprimé
        if ($message->isDeleted()) {
            throw $this->createNotFoundException('Ce message a été supprimé.');
        }
        
        $form = $this->createForm(MessageFormType::class, $message, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $message->setIsEdited(true);
            $message->setUpdatedAt(new \DateTime());
            
            $this->entityManager->flush();
            
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                
                return $this->render('messaging/message/update_stream.html.twig', [
                    'message' => $message
                ]);
            }
            
            return $this->redirectToRoute('app_messaging_conversation_show', [
                'id' => $message->getConversation()->getId()
            ]);
        }
        
        return $this->render('messaging/message/edit.html.twig', [
            'form' => $form,
            'message' => $message
        ]);
    }

    #[Route('/messages/{id}/delete', name: 'app_messaging_message_delete', methods: ['POST'])]
    public function delete(Request $request, Message $message): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est l'auteur du message ou un modérateur
        $isAuthor = $message->getAuthor() === $user;
        $isModerator = false;
        
        if (!$isAuthor) {
            // Vérifier si l'utilisateur est modérateur de la conversation
            $participant = $this->participantRepository->findOneByConversationAndUser(
                $message->getConversation(), 
                $user
            );
            
            if ($participant && in_array($participant->getRole(), ['admin', 'moderator'])) {
                $isModerator = true;
            }
        }
        
        if (!$isAuthor && !$isModerator) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce message.');
        }
        
        if ($this->isCsrfTokenValid('delete'.$message->getId(), $request->request->get('_token'))) {
            // On ne supprime pas réellement, on marque comme supprimé
            $message->setIsDeleted(true);
            $message->setContent('[Message supprimé]');
            $message->setAttachments(null);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Message supprimé avec succès.');
        }
        
        return $this->redirectToRoute('app_messaging_conversation_show', [
            'id' => $message->getConversation()->getId()
        ]);
    }

    #[Route('/messages/search', name: 'app_messaging_message_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $query = $request->query->get('q');
        $results = [];
        
        if ($query && strlen($query) >= 3) {
            $results = $this->messageRepository->searchByContent($query, $user);
        }
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('messaging/message/_search_results.html.twig', [
                'results' => $results,
                'query' => $query
            ]);
        }
        
        return $this->render('messaging/message/search.html.twig', [
            'results' => $results,
            'query' => $query
        ]);
    }

    #[Route('/messages/{id}/reactions/toggle/{reaction}', name: 'app_messaging_message_toggle_reaction', methods: ['POST'])]
    public function toggleReaction(Request $request, Message $message, string $reaction): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a accès à ce message
        $participant = $this->participantRepository->findOneByConversationAndUser(
            $message->getConversation(), 
            $user
        );
        
        if (!$participant || !$participant->isActive() || !$participant->canRead()) {
            return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
        
        // Idéalement, on utiliserait une entité dédiée aux réactions
        // Pour simplifier, on stocke les réactions directement dans le message
        // Dans une réelle implémentation, il faudrait créer une entité MessageReaction
        
        $attachments = $message->getAttachments() ?: [];
        $reactions = $attachments['reactions'] ?? [];
        
        if (!isset($reactions[$reaction])) {
            $reactions[$reaction] = [];
        }
        
        $userId = $user->getId();
        
        if (in_array($userId, $reactions[$reaction])) {
            // Supprimer la réaction
            $reactions[$reaction] = array_filter($reactions[$reaction], function($id) use ($userId) {
                return $id !== $userId;
            });
            
            // Nettoyer si la réaction n'a plus d'utilisateurs
            if (empty($reactions[$reaction])) {
                unset($reactions[$reaction]);
            }
        } else {
            // Ajouter la réaction
            $reactions[$reaction][] = $userId;
        }
        
        $attachments['reactions'] = $reactions;
        $message->setAttachments($attachments);
        
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'reactions' => $reactions
        ]);
    }
} 