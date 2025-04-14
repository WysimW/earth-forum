<?php

namespace App\Controller\Admin;

use App\Entity\Npc;
use App\Form\Admin\AdminNPCType;
use App\Repository\NpcRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/npc')]
#[IsGranted('ROLE_ADMIN')]
class AdminNpcController extends AbstractController
{
    #[Route('/', name: 'app_admin_npc_index', methods: ['GET'])]
    public function index(NpcRepository $npcRepository): Response
    {
        return $this->render('admin/npc/index.html.twig', [
            'npcs' => $npcRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_npc_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $npc = new Npc();
        $form = $this->createForm(AdminNPCType::class, $npc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($npc);
            $entityManager->flush();

            $this->addFlash('success', 'Le PNJ a été créé avec succès.');

            return $this->redirectToRoute('app_admin_npc_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/npc/new.html.twig', [
            'editMode' => false,
            'npc' => $npc,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_npc_show', methods: ['GET'])]
    public function show(Npc $npc): Response
    {
        return $this->render('admin/npc/show.html.twig', [
            'npc' => $npc,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_npc_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminNPCType::class, $npc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le PNJ a été modifié avec succès.');

            return $this->redirectToRoute('app_admin_npc_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/npc/edit.html.twig', [
            'editMode' => true,
            'npc' => $npc,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_npc_delete', methods: ['POST'])]
    public function delete(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$npc->getId(), $request->request->get('_token'))) {
            $entityManager->remove($npc);
            $entityManager->flush();

            $this->addFlash('success', 'Le PNJ a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_npc_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/validate', name: 'app_admin_npc_validate', methods: ['POST'])]
    public function validate(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('validate'.$npc->getId(), $request->request->get('_token'))) {
            $npc->setStatus(Npc::STATUS_VALIDATED);
            $npc->setValidatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Le PNJ a été validé avec succès.');
        }

        return $this->redirectToRoute('app_admin_npc_show', ['id' => $npc->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reject', name: 'app_admin_npc_reject', methods: ['POST'])]
    public function reject(Request $request, Npc $npc, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('reject'.$npc->getId(), $request->request->get('_token'))) {
            $npc->setStatus(Npc::STATUS_REJECTED);
            $npc->setStatusMessage($request->request->get('rejection_reason'));
            $entityManager->flush();

            $this->addFlash('success', 'Le PNJ a été rejeté.');
        }

        return $this->redirectToRoute('app_admin_npc_show', ['id' => $npc->getId()], Response::HTTP_SEE_OTHER);
    }
} 