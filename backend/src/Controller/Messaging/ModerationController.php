<?php

namespace App\Controller\Messaging;

use App\Entity\Messaging\Conversation;
use App\Entity\Messaging\ConversationParticipant;
use App\Entity\Messaging\Message;
use App\Entity\Messaging\MessageReport;
use App\Entity\User;
use App\Form\Messaging\MessageReportFormType;
use App\Form\Messaging\ModerationActionFormType;
use App\Repository\Messaging\ConversationParticipantRepository;
use App\Repository\Messaging\MessageReportRepository;
use App\Repository\Messaging\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messaging/moderation')]
#[IsGranted('ROLE_USER')]
class ModerationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private MessageReportRepository $reportRepository,
        private ConversationParticipantRepository $participantRepository
    ) {
    }

    #[Route('/reports', name: 'app_messaging_moderation_reports', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function listReports(Request $request): Response
    {
        $limit = $request->query->getInt('limit', 20);
        $offset = $request->query->getInt('offset', 0);
        
        $reports = $this->reportRepository->findPendingReports($limit, $offset);
        $totalCount = $this->reportRepository->countPendingReports();
        
        return $this->render('messaging/moderation/reports.html.twig', [
            'reports' => $reports,
            'totalCount' => $totalCount,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    #[Route('/conversations/{id}/reports', name: 'app_messaging_moderation_conversation_reports', methods: ['GET'])]
    public function listConversationReports(Conversation $conversation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est modérateur de cette conversation
        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$participant || !$participant->canModerate()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits de modération pour cette conversation.');
        }
        
        $reports = $this->reportRepository->findPendingByConversation($conversation->getId());
        
        return $this->render('messaging/moderation/conversation_reports.html.twig', [
            'conversation' => $conversation,
            'reports' => $reports
        ]);
    }

    #[Route('/messages/{id}/report', name: 'app_messaging_message_report', methods: ['GET', 'POST'])]
    public function reportMessage(Request $request, Message $message): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est membre de la conversation
        $conversation = $message->getConversation();
        $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$participant || !$participant->isActive()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas membre de cette conversation.');
        }
        
        // Vérifier que l'utilisateur n'a pas déjà signalé ce message
        if ($this->reportRepository->isMessageReportedByUser($message->getId(), $user)) {
            $this->addFlash('warning', 'Vous avez déjà signalé ce message.');
            return $this->redirectToRoute('app_messaging_conversation_show', ['id' => $conversation->getId()]);
        }
        
        // Créer un nouveau rapport
        $report = new MessageReport();
        $report->setMessage($message)
               ->setReporter($user);
        
        $form = $this->createForm(MessageReportFormType::class, $report);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Marquer le message comme signalé
            $message->setIsFlagged(true);
            
            // Enregistrer le rapport
            $this->entityManager->persist($report);
            $this->entityManager->flush();
            
            // Notification pour les modérateurs (à implémenter si besoin)
            
            $this->addFlash('success', 'Le message a été signalé et sera examiné par un modérateur.');
            return $this->redirectToRoute('app_messaging_conversation_show', ['id' => $conversation->getId()]);
        }
        
        return $this->render('messaging/moderation/report_message.html.twig', [
            'message' => $message,
            'form' => $form->createView()
        ]);
    }

    #[Route('/reports/{id}/handle', name: 'app_messaging_report_handle', methods: ['GET', 'POST'])]
    public function handleReport(Request $request, MessageReport $report): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est modérateur/admin global ou de cette conversation
        $conversation = $report->getMessage()->getConversation();
        $isGlobalModerator = $this->isGranted('ROLE_MODERATOR');
        
        if (!$isGlobalModerator) {
            $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
            
            if (!$participant || !$participant->canModerate()) {
                throw $this->createAccessDeniedException('Vous n\'avez pas les droits de modération pour cette conversation.');
            }
        }
        
        // Vérifier que le signalement est toujours en attente
        if (!$report->isPending()) {
            $this->addFlash('warning', 'Ce signalement a déjà été traité.');
            return $this->redirectToRoute('app_messaging_moderation_reports');
        }
        
        $form = $this->createForm(ModerationActionFormType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $form->get('action')->getData();
            $moderationNotes = $form->get('moderationNotes')->getData();
            
            // Traiter le signalement selon l'action choisie
            switch ($action) {
                case 'approve':
                    // Approuver le signalement et supprimer le message
                    $report->setStatus(MessageReport::STATUS_APPROVED)
                           ->setModerationNotes($moderationNotes)
                           ->setModerator($user)
                           ->setResolvedAt(new \DateTime());
                    
                    $message = $report->getMessage();
                    $message->setIsDeleted(true)
                            ->setIsFlagged(false)
                            ->setContent('Ce message a été supprimé par un modérateur.');
                    break;
                    
                case 'reject':
                    // Rejeter le signalement
                    $report->setStatus(MessageReport::STATUS_REJECTED)
                           ->setModerationNotes($moderationNotes)
                           ->setModerator($user)
                           ->setResolvedAt(new \DateTime());
                    
                    // Vérifier si c'était le dernier signalement actif pour ce message
                    $message = $report->getMessage();
                    if ($message->countActiveReports() === 0) {
                        $message->setIsFlagged(false);
                    }
                    break;
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le signalement a été traité avec succès.');
            
            // Rediriger vers la liste des signalements ou des signalements de la conversation
            if ($isGlobalModerator) {
                return $this->redirectToRoute('app_messaging_moderation_reports');
            } else {
                return $this->redirectToRoute('app_messaging_moderation_conversation_reports', [
                    'id' => $conversation->getId()
                ]);
            }
        }
        
        return $this->render('messaging/moderation/handle_report.html.twig', [
            'report' => $report,
            'form' => $form->createView()
        ]);
    }

    #[Route('/messages/{id}/flag', name: 'app_messaging_message_flag', methods: ['POST'])]
    public function flagMessage(Request $request, Message $message): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est modérateur/admin global ou de cette conversation
        $conversation = $message->getConversation();
        $isGlobalModerator = $this->isGranted('ROLE_MODERATOR');
        
        if (!$isGlobalModerator) {
            $participant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
            
            if (!$participant || !$participant->canModerate()) {
                return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
            }
        }
        
        // Marquer le message comme signalé
        $message->setIsFlagged(!$message->isFlagged());
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'flagged' => $message->isFlagged()
        ]);
    }

    #[Route('/participants/{id}/set-role', name: 'app_messaging_participant_set_role', methods: ['POST'])]
    public function setParticipantRole(Request $request, ConversationParticipant $participant): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est admin de cette conversation
        $conversation = $participant->getConversation();
        $adminParticipant = $this->participantRepository->findOneByConversationAndUser($conversation, $user);
        
        if (!$adminParticipant || $adminParticipant->getRole() !== ConversationParticipant::ROLE_ADMIN) {
            return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
        
        // Récupérer le nouveau rôle
        $data = json_decode($request->getContent(), true);
        $newRole = $data['role'] ?? null;
        
        if (!in_array($newRole, [ConversationParticipant::ROLE_ADMIN, ConversationParticipant::ROLE_MODERATOR, ConversationParticipant::ROLE_MEMBER])) {
            return new JsonResponse(['success' => false, 'message' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
        }
        
        // Si l'utilisateur essaie de rétrograder le seul admin, refuser
        if ($participant->getRole() === ConversationParticipant::ROLE_ADMIN && $newRole !== ConversationParticipant::ROLE_ADMIN) {
            $admins = $this->participantRepository->findAdminsByConversation($conversation);
            if (count($admins) <= 1) {
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'Impossible de rétrograder le seul administrateur'
                ], Response::HTTP_BAD_REQUEST);
            }
        }
        
        // Mettre à jour le rôle
        $participant->setRole($newRole);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'role' => $newRole,
            'roleLabel' => $participant->getRoleLabel()
        ]);
    }

    #[Route('/reports/count', name: 'app_messaging_moderation_reports_count', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function countPendingReports(): JsonResponse
    {
        $count = $this->reportRepository->countPendingReports();
        
        return new JsonResponse([
            'count' => $count
        ]);
    }
} 