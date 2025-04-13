<?php

namespace App\Controller\Admin;

use App\Entity\CharacterRelation;
use App\Form\CharacterRelationType;
use App\Repository\CharacterRelationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/character-relation')]
class CharacterRelationController extends AbstractController
{
    #[Route('/', name: 'admin_character_relation_index', methods: ['GET'])]
    public function index(CharacterRelationRepository $characterRelationRepository): Response
    {
        return $this->render('admin/character_relation/index.html.twig', [
            'character_relations' => $characterRelationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_character_relation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $characterRelation = new CharacterRelation();
        $form = $this->createForm(CharacterRelationType::class, $characterRelation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir la date de création
            $characterRelation->setCreatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($characterRelation);
            $entityManager->flush();

            $this->addFlash('success', 'La relation a été créée avec succès.');

            return $this->redirectToRoute('admin_character_relation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/character_relation/new.html.twig', [
            'character_relation' => $characterRelation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_character_relation_show', methods: ['GET'])]
    public function show(CharacterRelation $characterRelation): Response
    {
        return $this->render('admin/character_relation/show.html.twig', [
            'character_relation' => $characterRelation,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_character_relation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CharacterRelation $characterRelation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CharacterRelationType::class, $characterRelation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour la date de modification
            $characterRelation->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->flush();

            $this->addFlash('success', 'La relation a été modifiée avec succès.');

            return $this->redirectToRoute('admin_character_relation_show', ['id' => $characterRelation->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/character_relation/edit.html.twig', [
            'character_relation' => $characterRelation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_character_relation_delete', methods: ['POST'])]
    public function delete(Request $request, CharacterRelation $characterRelation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$characterRelation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($characterRelation);
            $entityManager->flush();
            
            $this->addFlash('success', 'La relation a été supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_character_relation_index', [], Response::HTTP_SEE_OTHER);
    }
}