<?php

namespace App\Controller\Messaging;

use App\Entity\Messaging\Conversation;
use App\Entity\Messaging\ConversationParticipant;
use App\Entity\Messaging\Message;
use App\Entity\User;
use App\Form\Messaging\ConversationFormType;
use App\Form\Messaging\MessageFormType;
use App\Repository\Messaging\ConversationParticipantRepository;
use App\Repository\Messaging\ConversationRepository;
use App\Repository\Messaging\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messaging/conversations')]
#[IsGranted('ROLE_USER')]
class ConversationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ConversationRepository $conversationRepository,
        private ConversationParticipantRepository $participantRepository,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository
    ) {
    }

    #[Route('/', name: 'app_messaging_conversations_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $conversations = $this->conversationRepository->findByUser($user);
        $unreadMessages = [];
        
        foreach ($conversations as $conversation) {
            $unreadMessages[$conversation->getId()] = $this->messageRepository->countUnreadByConversation($conversation, $user);
        }
        
        return $this->render('messaging/conversation/index.html.twig', [
            'conversations' => $conversations,
            'unreadMessages' => $unreadMessages,
        ]);
    }

    #[Route('/new', name: 'app_messaging_conversation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $conversation = new Conversation();
        $conversation->setCreator($user);
        
        $form = $this->createForm(ConversationFormType::class, $conversation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde de la conversation
            $this->entityManager->persist($conversation);
            
            // Ajout du créateur comme admin
            $participant = new ConversationParticipant();
            $participant->setConversation($conversation)
                ->setUser($user)
                ->setRole(ConversationParticipant::ROLE_ADMIN);
            
            $this->entityManager->persist($participant);
            
            // Ajout des autres membres sélectionnés
            if ($form->has('participants')) {
                $userIds = $form->get('participants')->getData();
                foreach ($userIds as $userId) {
                    $member = $this->userRepository->find($userId);
                    if ($member && $member !== $user) {
                        $memberParticipant = new ConversationParticipant();
                        $memberParticipant->setConversation($conversation)
                            ->setUser($member)
                            ->setRole(ConversationParticipant::ROLE_MEMBER);
                        
                        $this->entityManager->persist($memberParticipant);
                    }
                }
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Conversation créée avec succès.');
            
            return $this->redirectToRoute('app_messaging_conversation_show', [
                'id' => $conversation->getId()
            ]);
        }
        
        return $this->render('messaging/conversation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_messaging_conversation_show', methods: ['GET'])]
    public function show(Conversation $conversation, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est participant à cette conversation
        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$participant || !$participant->isActive()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette conversation.');
        }
        
        // Mettre à jour la date de dernière lecture
        $participant->setLastReadAt(new \DateTime());
        $this->entityManager->flush();
        
        // Récupérer les messages (pagination possible à ajouter)
        $messages = $this->messageRepository->findByConversation($conversation, 100, 0);
        
        // Récupérer tous les participants
        $participants = $this->participantRepository->findActiveByConversation($conversation);
        
        // Créer le formulaire pour envoyer un message
        $message = new Message();
        $message->setAuthor($user);
        $message->setConversation($conversation);
        
        $form = $this->createForm(MessageFormType::class, $message, [
            'action' => $this->generateUrl('app_messaging_messages_create', ['id' => $conversation->getId()])
        ]);
        
        return $this->render('messaging/conversation/show.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
            'participants' => $participants,
            'currentParticipant' => $participant,
            'form' => $form
        ]);
    }

    #[Route('/{id}/edit', name: 'app_messaging_conversation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Conversation $conversation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est admin de cette conversation
        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$participant || $participant->getRole() !== ConversationParticipant::ROLE_ADMIN) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de modifier cette conversation.');
        }
        
        $form = $this->createForm(ConversationFormType::class, $conversation, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $conversation->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Conversation modifiée avec succès.');
            
            return $this->redirectToRoute('app_messaging_conversation_show', [
                'id' => $conversation->getId()
            ]);
        }
        
        return $this->render('messaging/conversation/edit.html.twig', [
            'form' => $form,
            'conversation' => $conversation
        ]);
    }

    #[Route('/{id}/archive', name: 'app_messaging_conversation_archive', methods: ['POST'])]
    public function archive(Request $request, Conversation $conversation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est admin de cette conversation
        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$participant || $participant->getRole() !== ConversationParticipant::ROLE_ADMIN) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit d\'archiver cette conversation.');
        }
        
        if ($this->isCsrfTokenValid('archive'.$conversation->getId(), $request->request->get('_token'))) {
            $conversation->setIsArchived(true);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Conversation archivée avec succès.');
        }
        
        return $this->redirectToRoute('app_messaging_conversations_index');
    }

    #[Route('/{id}/leave', name: 'app_messaging_conversation_leave', methods: ['POST'])]
    public function leave(Request $request, Conversation $conversation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est participant à cette conversation
        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$participant || !$participant->isActive()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas membre de cette conversation.');
        }
        
        if ($this->isCsrfTokenValid('leave'.$conversation->getId(), $request->request->get('_token'))) {
            // Si l'utilisateur est le seul admin, interdire de quitter
            if ($participant->getRole() === ConversationParticipant::ROLE_ADMIN) {
                $admins = $this->participantRepository->findAdminsByConversation($conversation);
                
                if (count($admins) <= 1) {
                    $this->addFlash('error', 'Vous ne pouvez pas quitter la conversation car vous êtes le seul administrateur.');
                    return $this->redirectToRoute('app_messaging_conversation_show', ['id' => $conversation->getId()]);
                }
            }
            
            // Désactiver la participation plutôt que de la supprimer
            $participant->setIsActive(false);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Vous avez quitté la conversation.');
        }
        
        return $this->redirectToRoute('app_messaging_conversations_index');
    }

    #[Route('/{id}/participants/add', name: 'app_messaging_conversation_add_participants', methods: ['POST'])]
    public function addParticipants(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier les droits
        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$participant || !$participant->canManageMembers()) {
            return new JsonResponse(['success' => false, 'message' => 'Droits insuffisants'], Response::HTTP_FORBIDDEN);
        }
        
        $data = json_decode($request->getContent(), true);
        $userIds = $data['userIds'] ?? [];
        $addedUsers = [];
        
        foreach ($userIds as $userId) {
            $member = $this->userRepository->find($userId);
            
            if (!$member) {
                continue;
            }
            
            // Vérifier si déjà membre
            $existingParticipant = $this->participantRepository->findOneByConversationAndUser($conversation, $member);
            
            if ($existingParticipant) {
                if (!$existingParticipant->isActive()) {
                    // Réactiver sa participation
                    $existingParticipant->setIsActive(true);
                    $addedUsers[] = [
                        'id' => $member->getId(),
                        'name' => $member->getPseudo()
                    ];
                }
            } else {
                // Ajouter comme nouveau membre
                $newParticipant = new ConversationParticipant();
                $newParticipant->setConversation($conversation)
                    ->setUser($member)
                    ->setRole(ConversationParticipant::ROLE_MEMBER);
                
                $this->entityManager->persist($newParticipant);
                $addedUsers[] = [
                    'id' => $member->getId(),
                    'name' => $member->getPseudo()
                ];
            }
        }
        
        if (!empty($addedUsers)) {
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => count($addedUsers) . ' utilisateur(s) ajouté(s) à la conversation',
                'users' => $addedUsers
            ]);
        }
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Aucun utilisateur ajouté'
        ]);
    }

    #[Route('/{id}/participants/{userId}/remove', name: 'app_messaging_conversation_remove_participant', methods: ['POST'])]
    public function removeParticipant(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier les droits
        $adminParticipant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$adminParticipant || !$adminParticipant->canManageMembers()) {
            return new JsonResponse(['success' => false, 'message' => 'Droits insuffisants'], Response::HTTP_FORBIDDEN);
        }
        
        $memberToRemove = $this->userRepository->find($userId);
        
        if (!$memberToRemove) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }
        
        // Ne pas permettre de s'expulser soi-même par cette méthode
        if ($memberToRemove === $user) {
            return new JsonResponse(['success' => false, 'message' => 'Vous ne pouvez pas vous retirer vous-même'], Response::HTTP_BAD_REQUEST);
        }
        
        $targetParticipant = $this->participantRepository->findOneByConversationAndUser($conversation, $memberToRemove);
        
        if (!$targetParticipant || !$targetParticipant->isActive()) {
            return new JsonResponse(['success' => false, 'message' => 'Cet utilisateur n\'est pas membre de la conversation'], Response::HTTP_BAD_REQUEST);
        }
        
        // On ne peut pas supprimer un admin si on n'est pas admin soi-même
        if ($targetParticipant->getRole() === ConversationParticipant::ROLE_ADMIN && $adminParticipant->getRole() !== ConversationParticipant::ROLE_ADMIN) {
            return new JsonResponse(['success' => false, 'message' => 'Vous ne pouvez pas retirer un administrateur'], Response::HTTP_FORBIDDEN);
        }
        
        // Désactiver la participation plutôt que de la supprimer
        $targetParticipant->setIsActive(false);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Participant retiré avec succès'
        ]);
    }
} 