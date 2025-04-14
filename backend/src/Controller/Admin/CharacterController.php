<?php

namespace App\Controller\Admin;

use App\Entity\Character;
use App\Form\Admin\AdminCharacterType;
use App\Repository\CharacterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/character')]
#[IsGranted('ROLE_ADMIN')]
class CharacterController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'admin_character_index', methods: ['GET'])]
    public function index(CharacterRepository $characterRepository): Response
    {
        return $this->render('admin/character/index.html.twig', [
            'characters' => $characterRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_character_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $character = new Character();
        $form = $this->createForm(AdminCharacterType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($character);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été créé avec succès.');
            return $this->redirectToRoute('admin_character_index');
        }

        return $this->render('admin/character/new.html.twig', [
            'character' => $character,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_character_show', methods: ['GET'])]
    public function show(Character $character): Response
    {
        return $this->render('admin/character/show.html.twig', [
            'character' => $character,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_character_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Character $character): Response
    {
        $form = $this->createForm(AdminCharacterType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été mis à jour avec succès.');
            return $this->redirectToRoute('admin_character_index');
        }

        return $this->render('admin/character/edit.html.twig', [
            'character' => $character,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_character_delete', methods: ['POST'])]
    public function delete(Request $request, Character $character): Response
    {
        if ($this->isCsrfTokenValid('delete'.$character->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($character);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_character_index');
    }

    #[Route('/{id}/reject', name: 'admin_character_reject', methods: ['POST'])]
    public function reject(Request $request, Character $character): Response
    {
        if ($this->isCsrfTokenValid('reject' . $character->getId(), $request->request->get('_token'))) {
            $rejectionReason = $request->request->get('rejection_reason');
            
            $character->setStatus('rejected');
            $character->setStatusMessage($rejectionReason);
            $character->setRejectedAt(new \DateTimeImmutable());
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le personnage a été rejeté avec succès.');
        }

        return $this->redirectToRoute('admin_character_index');
    }

    #[Route('/{id}/validate', name: 'admin_character_validate', methods: ['POST'])]
    public function validate(Request $request, Character $character): Response
    {
        if ($this->isCsrfTokenValid('validate' . $character->getId(), $request->request->get('_token'))) {
            $character->setStatus(Character::STATUS_VALIDATED);
            $character->setValidatedAt(new \DateTimeImmutable());
            $character->setStatusMessage('Validé par un administrateur');
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le personnage a été validé avec succès.');
        }

        return $this->redirectToRoute('admin_character_index');
    }
}