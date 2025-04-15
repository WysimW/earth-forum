<?php

namespace App\Controller;

use App\Entity\Npc;
use App\Form\NpcType;
use App\Repository\NpcRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/npc')]
class NpcController extends AbstractController
{
    #[Route('/', name: 'app_npc_index', methods: ['GET'])]
    public function index(NpcRepository $npcRepository): Response
    {
        return $this->render('npc/index.html.twig', [
            'npcs' => $npcRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_npc_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $npc = new Npc();
        $form = $this->createForm(NpcType::class, $npc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $npc->setUser($this->getUser());
            $entityManager->persist($npc);
            $entityManager->flush();

            return $this->redirectToRoute('app_npc_show', ['id' => $npc->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('npc/new.html.twig', [
            'npc' => $npc,
            'form' => $form,
        ]);
    }

    #[Route('/new/elseworld/{id}', name: 'app_npc_new_elseworld', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newForElseworld(Request $request, EntityManagerInterface $entityManager, \App\Entity\Elseworld $elseworld): Response
    {
        $npc = new Npc();
        $npc->setUser($this->getUser());
        $npc->setElseworld($elseworld);
        
        $form = $this->createForm(NpcType::class, $npc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $npc->setStatus(Npc::STATUS_DRAFT);
            $entityManager->persist($npc);
            $entityManager->flush();

            $this->addFlash('success', 'Votre PNJ a été créé avec succès dans l\'Elseworld ' . $elseworld->getName());
            return $this->redirectToRoute('app_npc_show', ['id' => $npc->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('npc/new.html.twig', [
            'npc' => $npc,
            'form' => $form,
            'elseworld' => $elseworld,
            'breadcrumbs' => [
                'Accueil' => $this->generateUrl('app_home'),
                'Elseworld: ' . $elseworld->getName() => $this->generateUrl('app_elseworld_show', ['id' => $elseworld->getId()]),
                'Nouveau PNJ' => $this->generateUrl('app_npc_new_elseworld', ['id' => $elseworld->getId()]),
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_npc_show', methods: ['GET'])]
    public function show(Npc $npc): Response
    {
        return $this->render('npc/show.html.twig', [
            'npc' => $npc,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_npc_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', subject: 'npc')]
    public function edit(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NpcType::class, $npc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_npc_show', ['id' => $npc->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('npc/edit.html.twig', [
            'npc' => $npc,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_npc_delete', methods: ['POST'])]
    #[IsGranted('delete', subject: 'npc')]
    public function delete(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$npc->getId(), $request->request->get('_token'))) {
            $entityManager->remove($npc);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_npc_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/validate', name: 'app_npc_validate', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function validate(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('validate'.$npc->getId(), $request->request->get('_token'))) {
            $npc->setStatus(Npc::STATUS_VALIDATED);
            $npc->setValidatedAt(new \DateTimeImmutable());
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_npc_show', ['id' => $npc->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reject', name: 'app_npc_reject', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function reject(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('reject'.$npc->getId(), $request->request->get('_token'))) {
            $npc->setStatus(Npc::STATUS_REJECTED);
            $npc->setStatusMessage($request->request->get('rejection_reason'));
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_npc_show', ['id' => $npc->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/update-status', name: 'app_npc_update_status', methods: ['POST'])]
    #[IsGranted('edit', subject: 'npc')]
    public function updateStatus(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        if ($npc->getUser() !== $this->getUser() && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier le statut de ce PNJ.');
        }
        
        $newStatus = $request->request->get('status');
        $statusMessage = $request->request->get('status_message');
        $moderationNote = $request->request->get('moderation_note');
        
        $oldStatus = $npc->getStatus();
        $npc->setStatus($newStatus);
        
        if ($statusMessage) {
            $npc->setStatusMessage($statusMessage);
        }
        
        if ($moderationNote && $this->isGranted('ROLE_MODERATOR')) {
            $npc->setModerationNote($moderationNote);
        }
        
        // Si le PNJ est validé et ne l'était pas déjà
        if ($newStatus === Npc::STATUS_VALIDATED && $oldStatus !== Npc::STATUS_VALIDATED) {
            $npc->setValidatedAt(new \DateTimeImmutable());
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Le statut du PNJ a été mis à jour avec succès.');
        
        return $this->redirectToRoute('app_npc_show', ['id' => $npc->getId()]);
    }
} 