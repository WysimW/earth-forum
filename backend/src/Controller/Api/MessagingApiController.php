<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Character;
use App\Entity\Messaging\Conversation;
use App\Entity\Messaging\ConversationParticipant;
use App\Entity\Messaging\Message;
use App\Entity\Messaging\MessageReport;
use App\Entity\User;
use App\Repository\Messaging\ConversationParticipantRepository;
use App\Repository\Messaging\ConversationRepository;
use App\Repository\Messaging\MessageRepository;
use App\Repository\Messaging\MessageReportRepository;
use App\Repository\UserRepository;
use App\Service\Messaging\MessagingJsonSerializer;
use App\Service\UserSanctionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/messaging')]
#[IsGranted('ROLE_USER')]
class MessagingApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationParticipantRepository $participantRepository,
        private readonly MessageRepository $messageRepository,
        private readonly MessageReportRepository $messageReportRepository,
        private readonly UserRepository $userRepository,
        private readonly UserSanctionService $sanctionService,
        private readonly MessagingJsonSerializer $messagingJsonSerializer,
    ) {
    }

    #[Route('/unread/count', name: 'api_messaging_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $total = $this->messageRepository->countAllUnread($user);
        $conversations = $this->conversationRepository->findByUser($user);
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
            'conversations' => $conversationsUnread,
        ]);
    }

    #[Route('/conversations', name: 'api_messaging_conversations_list', methods: ['GET'])]
    public function listConversations(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->sanctionService->canAccessMessaging($user)) {
            return $this->jsonMessagingRestriction($user);
        }

        $conversations = $this->conversationRepository->findByUser($user);
        $items = [];
        foreach ($conversations as $conversation) {
            if ($conversation->getType() !== Conversation::TYPE_PRIVATE) {
                continue;
            }

            $unread = $this->messageRepository->countUnreadByConversation($conversation, $user);
            $last = $this->messageRepository->findLastMessageInConversation($conversation);
            $updatedAt = $conversation->getUpdatedAt() ?? $conversation->getCreatedAt();
            $counterpart = $this->counterpartUser($conversation, $user);
            $avatarUrl = '';
            if ($counterpart instanceof User) {
                $avatarUrl = $this->messagingJsonSerializer->userAvatar($counterpart);
            }

            $items[] = [
                'id' => $conversation->getId(),
                'type' => $conversation->getType(),
                'title' => $this->privateConversationTitle($conversation, $user),
                'counterpartAvatar' => '' !== $avatarUrl ? $avatarUrl : null,
                'lastMessagePreview' => $last ? $this->truncatePreview((string) $last->getContent()) : null,
                'lastMessageAuthorIsSelf' => $last ? $last->getAuthor() === $user : false,
                'lastMessageAt' => $last ? $last->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'unreadCount' => $unread,
                'updatedAt' => $updatedAt instanceof \DateTimeInterface ? $updatedAt->format('Y-m-d H:i:s') : null,
            ];
        }

        return new JsonResponse(['conversations' => $items]);
    }

    #[Route('/members/search', name: 'api_messaging_members_search', methods: ['GET'])]
    public function searchMembers(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->sanctionService->canAccessMessaging($user)) {
            return $this->jsonMessagingRestriction($user);
        }

        $q = (string) $request->query->get('q', '');
        $limit = (int) $request->query->get('limit', 20);
        $limit = min(max($limit, 1), 30);

        $members = $this->userRepository->searchActiveMembersByPseudoFragment(
            $q,
            (int) $user->getId(),
            $limit,
        );

        $items = [];
        foreach ($members as $member) {
            $avatarUrl = $this->messagingJsonSerializer->userAvatar($member);
            $items[] = [
                'id' => $member->getId(),
                'pseudo' => $member->getPseudo(),
                'avatar' => '' !== $avatarUrl ? $avatarUrl : null,
            ];
        }

        return new JsonResponse(['members' => $items]);
    }

    #[Route('/conversations', name: 'api_messaging_conversations_create', methods: ['POST'])]
    public function createConversation(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->sanctionService->canAccessMessaging($user)) {
            return $this->jsonMessagingRestriction($user);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        $targetUser = null;
        if (isset($data['targetUserId']) && $data['targetUserId'] !== '') {
            $targetUser = $this->userRepository->find((int) $data['targetUserId']);
        } elseif (isset($data['pseudo']) && is_string($data['pseudo']) && trim($data['pseudo']) !== '') {
            $targetUser = $this->userRepository->findOneBy(['pseudo' => trim($data['pseudo'])]);
        }

        if (!$targetUser instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (!$targetUser->isActive()) {
            return new JsonResponse(['error' => 'Ce compte est inactif'], Response::HTTP_BAD_REQUEST);
        }

        if ($targetUser === $user) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas ouvrir une conversation avec vous-même'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->conversationRepository->findPrivateConversation($user, $targetUser);
        if ($existing) {
            if ($existing->isArchived()) {
                $existing->setIsArchived(false);
                $this->entityManager->flush();
            }

            return new JsonResponse($this->serializeConversationResponse($existing, $user), Response::HTTP_OK);
        }

        $pseudoA = (string) $user->getPseudo();
        $pseudoB = (string) $targetUser->getPseudo();
        $name = $pseudoA <= $pseudoB
            ? 'MP : '.$pseudoA.' & '.$pseudoB
            : 'MP : '.$pseudoB.' & '.$pseudoA;

        $conversation = new Conversation();
        $conversation->setCreator($user);
        $conversation->setType(Conversation::TYPE_PRIVATE);
        $conversation->setName($name);

        $admin = new ConversationParticipant();
        $admin->setConversation($conversation)
            ->setUser($user)
            ->setRole(ConversationParticipant::ROLE_ADMIN);

        $member = new ConversationParticipant();
        $member->setConversation($conversation)
            ->setUser($targetUser)
            ->setRole(ConversationParticipant::ROLE_MEMBER);

        $this->entityManager->persist($conversation);
        $this->entityManager->persist($admin);
        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return new JsonResponse($this->serializeConversationResponse($conversation, $user), Response::HTTP_CREATED);
    }

    #[Route('/conversations/{id}', name: 'api_messaging_conversation_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getConversation(Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($conversation->getType() !== Conversation::TYPE_PRIVATE) {
            return new JsonResponse(['error' => 'Conversation non disponible'], Response::HTTP_FORBIDDEN);
        }

        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        if (!$participant || !$participant->isActive()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->sanctionService->canAccessMessaging($user)) {
            return $this->jsonMessagingRestriction($user);
        }

        $participants = [];
        foreach ($this->participantRepository->findActiveByConversation($conversation) as $p) {
            $u = $p->getUser();
            if (!$u instanceof User) {
                continue;
            }
            $participants[] = [
                'id' => $u->getId(),
                'pseudo' => $u->getPseudo(),
                'avatar' => $this->messagingJsonSerializer->userAvatar($u),
            ];
        }

        return new JsonResponse([
            'conversation' => [
                'id' => $conversation->getId(),
                'type' => $conversation->getType(),
                'title' => $this->privateConversationTitle($conversation, $user),
                'name' => $conversation->getName(),
                'createdAt' => $conversation->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $conversation->getUpdatedAt() ? $conversation->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                'participants' => $participants,
                'canSendMessage' => $this->sanctionService->canSendMessage($user),
                'myRole' => $participant->getRole(),
                'canModerate' => $participant->canModerate(),
            ],
        ]);
    }

    #[Route('/conversations/{id}/messages', name: 'api_messaging_messages_list', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getMessages(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        if (!$participant || !$participant->isActive()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->sanctionService->canAccessMessaging($user)) {
            return $this->jsonMessagingRestriction($user);
        }

        $participant->setLastReadAt(new \DateTime());
        $this->entityManager->flush();

        $limit = min(100, max(1, $request->query->getInt('limit', 30)));
        $before = $request->query->get('before');
        $beforeId = null;
        if ($before !== null && $before !== '' && ctype_digit((string) $before)) {
            $beforeId = (int) $before;
        }

        $messages = $this->messageRepository->findRecentMessagesForChat($conversation, $limit, $beforeId);

        $messagesData = $this->messagingJsonSerializer->serializeMessagesForViewer($messages, $user);

        $hasMoreOlder = false;
        if ($messages !== []) {
            $oldestId = $messages[0]->getId();
            if ($oldestId !== null) {
                $hasMoreOlder = $this->messageRepository->countMessagesOlderThan($conversation, $oldestId) > 0;
            }
        }

        return new JsonResponse([
            'messages' => $messagesData,
            'hasMore' => $hasMoreOlder,
            'totalCount' => $this->messageRepository->countByConversation($conversation),
            'participantsCount' => \count($this->participantRepository->findActiveByConversation($conversation)),
        ]);
    }

    #[Route('/conversations/{id}/messages', name: 'api_messaging_messages_send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sendMessage(Request $request, Conversation $conversation, ValidatorInterface $validator): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        if (!$participant || !$participant->isActive() || !$participant->canWrite()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->sanctionService->canSendMessage($user)) {
            $restrictions = $this->sanctionService->checkMessagingRestrictions($user);

            return new JsonResponse(['error' => $restrictions['restrictionMessage']], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['content']) || '' === trim((string) $data['content'])) {
            return new JsonResponse(['error' => 'Le contenu du message ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        $message = new Message();
        $message->setAuthor($user)
            ->setConversation($conversation)
            ->setContent((string) $data['content']);

        if (!empty($data['isRoleplay']) && !empty($data['characterId'])) {
            $character = $this->entityManager->getRepository(Character::class)->find((int) $data['characterId']);
            if ($character instanceof Character && $character->getUser() === $user) {
                $message->setIsRoleplay(true)
                    ->setCharacter($character);
            }
        }

        $errors = $validator->validate($message);
        if (\count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['error' => 'Validation échouée', 'messages' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($message);
        $conversation->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        $mid = $message->getId();
        $reported = null !== $mid && $this->messageReportRepository->isMessageReportedByUser($mid, $user);
        $messageData = $this->messagingJsonSerializer->serializeMessage($message, $user, $reported);

        return new JsonResponse(['success' => true, 'message' => $messageData]);
    }

    #[Route('/conversations/{id}/messages/{messageId}', name: 'api_messaging_message_update', methods: ['PATCH'], requirements: ['id' => '\d+', 'messageId' => '\d+'])]
    public function updateMessage(Request $request, Conversation $conversation, int $messageId, ValidatorInterface $validator): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        if (!$participant || !$participant->isActive() || !$participant->canWrite()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->sanctionService->canSendMessage($user)) {
            $restrictions = $this->sanctionService->checkMessagingRestrictions($user);

            return new JsonResponse(['error' => $restrictions['restrictionMessage']], Response::HTTP_FORBIDDEN);
        }

        $message = $this->messageRepository->find($messageId);
        if (!$message instanceof Message || $message->getConversation() !== $conversation) {
            return new JsonResponse(['error' => 'Message introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($message->getAuthor() !== $user) {
            return new JsonResponse(['error' => 'Vous ne pouvez modifier que vos propres messages'], Response::HTTP_FORBIDDEN);
        }

        if ($message->isDeleted()) {
            return new JsonResponse(['error' => 'Ce message ne peut pas être modifié'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['content']) || '' === trim((string) $data['content'])) {
            return new JsonResponse(['error' => 'Le contenu du message ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        $message->setContent((string) $data['content'])
            ->setIsEdited(true)
            ->setUpdatedAt(new \DateTime());

        $errors = $validator->validate($message);
        if (\count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['error' => 'Validation échouée', 'messages' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $conversation->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        $reported = $this->messageReportRepository->isMessageReportedByUser($messageId, $user);
        $messageData = $this->messagingJsonSerializer->serializeMessage($message, $user, $reported);

        return new JsonResponse(['success' => true, 'message' => $messageData]);
    }

    #[Route('/conversations/{id}/messages/{messageId}', name: 'api_messaging_message_delete', methods: ['DELETE'], requirements: ['id' => '\d+', 'messageId' => '\d+'])]
    public function deleteMessage(Conversation $conversation, int $messageId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        if (!$participant || !$participant->isActive() || !$participant->canWrite()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $message = $this->messageRepository->find($messageId);
        if (!$message instanceof Message || $message->getConversation() !== $conversation) {
            return new JsonResponse(['error' => 'Message introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($message->getAuthor() !== $user) {
            return new JsonResponse(['error' => 'Vous ne pouvez supprimer que vos propres messages'], Response::HTTP_FORBIDDEN);
        }

        if ($message->isDeleted()) {
            return new JsonResponse(['error' => 'Ce message est déjà supprimé'], Response::HTTP_BAD_REQUEST);
        }

        $message->setIsDeleted(true)
            ->setContent(Message::CONTENT_PLACEHOLDER_USER_DELETED)
            ->setUpdatedAt(new \DateTime());

        $conversation->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        $reported = $this->messageReportRepository->isMessageReportedByUser($messageId, $user);
        $messageData = $this->messagingJsonSerializer->serializeMessage($message, $user, $reported);

        return new JsonResponse(['success' => true, 'message' => $messageData]);
    }

    #[Route('/conversations/{id}/messages/{messageId}/report', name: 'api_messaging_message_report', methods: ['POST'], requirements: ['id' => '\d+', 'messageId' => '\d+'])]
    public function reportMessage(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        if (!$participant || !$participant->isActive()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->sanctionService->canAccessMessaging($user)) {
            return $this->jsonMessagingRestriction($user);
        }

        $message = $this->messageRepository->find($messageId);
        if (!$message instanceof Message || $message->getConversation() !== $conversation) {
            return new JsonResponse(['error' => 'Message introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($message->isDeleted()) {
            return new JsonResponse(['error' => 'Ce message ne peut pas être signalé'], Response::HTTP_BAD_REQUEST);
        }

        if ($message->getAuthor() === $user) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas signaler vos propres messages'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->messageReportRepository->isMessageReportedByUser($messageId, $user)) {
            return new JsonResponse(['error' => 'Vous avez déjà signalé ce message'], Response::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        $reason = isset($data['reason']) && \is_string($data['reason']) ? trim($data['reason']) : '';
        $allowedReasons = [
            MessageReport::REASON_INAPPROPRIATE,
            MessageReport::REASON_HARASSMENT,
            MessageReport::REASON_SPAM,
            MessageReport::REASON_OFFENSIVE,
            MessageReport::REASON_OTHER,
        ];
        if (!\in_array($reason, $allowedReasons, true)) {
            return new JsonResponse(['error' => 'Motif de signalement invalide'], Response::HTTP_BAD_REQUEST);
        }

        $details = isset($data['details']) && \is_string($data['details']) ? trim($data['details']) : '';
        if (mb_strlen($details) > 4000) {
            return new JsonResponse(['error' => 'Les détails sont trop longs (4000 caractères maximum)'], Response::HTTP_BAD_REQUEST);
        }

        $report = new MessageReport();
        $report->setMessage($message)
            ->setReporter($user)
            ->setReason($reason)
            ->setDetails('' !== $details ? $details : null)
            ->setReportedContentSnapshot((string) $message->getContent());

        $message->setIsFlagged(true);
        $this->entityManager->persist($report);
        $this->entityManager->flush();

        $messageData = $this->messagingJsonSerializer->serializeMessage($message, $user, true);

        return new JsonResponse(['success' => true, 'message' => $messageData]);
    }

    #[Route('/moderation/pending-reports', name: 'api_messaging_moderation_pending_reports', methods: ['GET'])]
    public function listGlobalPendingReports(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $offset = max(0, $request->query->getInt('offset', 0));
        $reports = $this->messageReportRepository->findPendingReports($limit, $offset);
        $total = $this->messageReportRepository->countPendingReports();

        return new JsonResponse([
            'reports' => array_map(fn (MessageReport $r) => $this->serializeMessageReport($r), $reports),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    #[Route('/moderation/pending-reports/count', name: 'api_messaging_moderation_pending_count', methods: ['GET'])]
    public function countGlobalPendingReports(): JsonResponse
    {
        if (!$this->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'count' => $this->messageReportRepository->countPendingReports(),
        ]);
    }

    #[Route('/conversations/{id}/moderation/pending-reports', name: 'api_messaging_conversation_moderation_pending', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function listConversationPendingReports(Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($conversation->getType() !== Conversation::TYPE_PRIVATE) {
            return new JsonResponse(['error' => 'Conversation non disponible'], Response::HTTP_FORBIDDEN);
        }

        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        if (!$participant || !$participant->isActive()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->isGranted('ROLE_MODERATOR') && !$participant->canModerate()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $reports = $this->messageReportRepository->findPendingByConversation((int) $conversation->getId());

        return new JsonResponse([
            'reports' => array_map(fn (MessageReport $r) => $this->serializeMessageReport($r), $reports),
        ]);
    }

    #[Route('/reports/{reportId}/resolve', name: 'api_messaging_report_resolve', methods: ['POST'], requirements: ['reportId' => '\d+'])]
    public function resolveMessageReport(Request $request, int $reportId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $report = $this->entityManager->find(MessageReport::class, $reportId);
        if (!$report instanceof MessageReport) {
            return new JsonResponse(['error' => 'Signalement introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->userCanHandleMessageReport($user, $report)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        if (!$report->isPending()) {
            return new JsonResponse(['error' => 'Ce signalement a déjà été traité'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        $action = isset($data['action']) && \is_string($data['action']) ? trim($data['action']) : '';
        if (!\in_array($action, ['approve', 'reject'], true)) {
            return new JsonResponse(['error' => 'Action invalide (approve ou reject)'], Response::HTTP_BAD_REQUEST);
        }

        $moderationNotes = isset($data['moderationNotes']) && \is_string($data['moderationNotes']) ? trim($data['moderationNotes']) : '';
        if ('' === $moderationNotes) {
            $moderationNotes = null;
        } elseif (mb_strlen($moderationNotes) > 5000) {
            return new JsonResponse(['error' => 'Notes trop longues (5000 caractères maximum)'], Response::HTTP_BAD_REQUEST);
        }

        if ('approve' === $action) {
            $report->setStatus(MessageReport::STATUS_APPROVED)
                ->setModerationNotes($moderationNotes)
                ->setModerator($user)
                ->setResolvedAt(new \DateTimeImmutable());

            $message = $report->getMessage();
            if ($message instanceof Message) {
                $message->setIsDeleted(true)
                    ->setIsFlagged(false)
                    ->setContent(Message::CONTENT_PLACEHOLDER_MODERATED);
            }
        } else {
            $report->setStatus(MessageReport::STATUS_REJECTED)
                ->setModerationNotes($moderationNotes)
                ->setModerator($user)
                ->setResolvedAt(new \DateTimeImmutable());

            $message = $report->getMessage();
            if ($message instanceof Message && 0 === $message->countActiveReports()) {
                $message->setIsFlagged(false);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/conversations/{id}/messages/{messageId}/moderation/flag', name: 'api_messaging_message_flag_toggle', methods: ['POST'], requirements: ['id' => '\d+', 'messageId' => '\d+'])]
    public function toggleMessageFlag(Conversation $conversation, int $messageId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->userCanModerateConversation($user, $conversation)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $message = $this->messageRepository->find($messageId);
        if (!$message instanceof Message || $message->getConversation() !== $conversation) {
            return new JsonResponse(['error' => 'Message introuvable'], Response::HTTP_NOT_FOUND);
        }

        $message->setIsFlagged(!$message->isFlagged());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'flagged' => $message->isFlagged(),
        ]);
    }

    #[Route('/conversations/{id}/participants/{participantId}/role', name: 'api_messaging_participant_set_role', methods: ['POST'], requirements: ['id' => '\d+', 'participantId' => '\d+'])]
    public function setParticipantRole(Request $request, Conversation $conversation, int $participantId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $adminParticipant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        if (!$adminParticipant || $adminParticipant->getRole() !== ConversationParticipant::ROLE_ADMIN) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $participant = $this->participantRepository->find($participantId);
        if (!$participant instanceof ConversationParticipant || $participant->getConversation() !== $conversation) {
            return new JsonResponse(['error' => 'Participant introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        $newRole = isset($data['role']) && \is_string($data['role']) ? trim($data['role']) : '';
        if (!\in_array($newRole, [ConversationParticipant::ROLE_ADMIN, ConversationParticipant::ROLE_MODERATOR, ConversationParticipant::ROLE_MEMBER], true)) {
            return new JsonResponse(['error' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (ConversationParticipant::ROLE_ADMIN === $participant->getRole() && ConversationParticipant::ROLE_ADMIN !== $newRole) {
            $admins = $this->participantRepository->findAdminsByConversation($conversation);
            if (\count($admins) <= 1) {
                return new JsonResponse(['error' => 'Impossible de rétrograder le seul administrateur'], Response::HTTP_BAD_REQUEST);
            }
        }

        $participant->setRole($newRole);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'role' => $newRole,
            'roleLabel' => $participant->getRoleLabel(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessageReport(MessageReport $report): array
    {
        $message = $report->getMessage();
        $conversation = $message?->getConversation();
        $author = $message?->getAuthor();
        $reporter = $report->getReporter();

        $body = $report->getBodyForModeration();
        $preview = trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? '');
        if (mb_strlen($preview) > 400) {
            $preview = mb_substr($preview, 0, 399).'…';
        }

        return [
            'id' => $report->getId(),
            'status' => $report->getStatus(),
            'reason' => $report->getReason(),
            'reasonLabel' => $report->getReasonLabel(),
            'details' => $report->getDetails(),
            'createdAt' => $report->getCreatedAt()?->format('Y-m-d H:i:s'),
            'conversation' => [
                'id' => $conversation?->getId(),
                'title' => $conversation instanceof Conversation && $reporter instanceof User
                    ? $this->privateConversationTitle($conversation, $reporter)
                    : null,
            ],
            'message' => [
                'id' => $message?->getId(),
                'authorPseudo' => $author?->getPseudo(),
            ],
            'reporter' => [
                'id' => $reporter?->getId(),
                'pseudo' => $reporter?->getPseudo(),
            ],
            'bodyPreview' => $preview,
        ];
    }

    private function userCanHandleMessageReport(User $user, MessageReport $report): bool
    {
        $message = $report->getMessage();
        if (!$message instanceof Message) {
            return false;
        }

        $conversation = $message->getConversation();
        if (!$conversation instanceof Conversation) {
            return false;
        }

        if ($this->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);

        return $participant instanceof ConversationParticipant && $participant->canModerate();
    }

    private function userCanModerateConversation(User $user, Conversation $conversation): bool
    {
        if ($this->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);

        return $participant instanceof ConversationParticipant && $participant->canModerate();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConversationResponse(Conversation $conversation, User $viewer): array
    {
        return [
            'conversation' => [
                'id' => $conversation->getId(),
                'type' => $conversation->getType(),
                'title' => $this->privateConversationTitle($conversation, $viewer),
                'name' => $conversation->getName(),
            ],
        ];
    }

    private function privateConversationTitle(Conversation $conversation, User $viewer): string
    {
        if ($conversation->getType() !== Conversation::TYPE_PRIVATE) {
            return (string) $conversation->getName();
        }

        $other = $this->counterpartUser($conversation, $viewer);
        if ($other instanceof User) {
            return (string) $other->getPseudo();
        }

        return (string) $conversation->getName();
    }

    /**
     * Autre participant actif (premier trouvé différent du lecteur).
     */
    private function counterpartUser(Conversation $conversation, User $viewer): ?User
    {
        foreach ($this->participantRepository->findActiveByConversation($conversation) as $participant) {
            $u = $participant->getUser();
            if ($u instanceof User && $u->getId() !== $viewer->getId()) {
                return $u;
            }
        }

        return null;
    }

    private function truncatePreview(string $content, int $max = 120): string
    {
        $content = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
        if (mb_strlen($content) <= $max) {
            return $content;
        }

        return mb_substr($content, 0, $max - 1).'…';
    }

    private function jsonMessagingRestriction(User $user): JsonResponse
    {
        $restrictions = $this->sanctionService->checkMessagingRestrictions($user);

        return new JsonResponse(['error' => $restrictions['restrictionMessage']], Response::HTTP_FORBIDDEN);
    }
}
