<?php

namespace App\Controller;

use App\Entity\Elseworld;
use App\Entity\Univers;
use App\Form\ElseworldType;
use App\Repository\ElseworldRepository;
use App\Repository\UniversRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/elseworld')]
class ElseworldController extends AbstractController
{
    #[Route('/', name: 'app_elseworld_index', methods: ['GET'])]
    public function index(ElseworldRepository $elseworldRepository): Response
    {
        return $this->render('elseworld/index.html.twig', [
            'elseworlds' => $elseworldRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_elseworld_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $elseworld = new Elseworld();
        $form = $this->createForm(ElseworldType::class, $elseworld);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer le slug à partir du nom
            $slug = $slugger->slug(strtolower($elseworld->getName()));
            $elseworld->setSlug($slug);
            
            $entityManager->persist($elseworld);
            $entityManager->flush();

            $this->addFlash('success', 'L\'Elseworld a été créé avec succès.');
            return $this->redirectToRoute('app_elseworld_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('elseworld/new.html.twig', [
            'elseworld' => $elseworld,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_elseworld_show', methods: ['GET'])]
    public function show(Elseworld $elseworld): Response
    {
        return $this->render('elseworld/show.html.twig', [
            'elseworld' => $elseworld,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_elseworld_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Elseworld $elseworld, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ElseworldType::class, $elseworld);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour le slug si le nom a changé
            $slug = $slugger->slug(strtolower($elseworld->getName()));
            $elseworld->setSlug($slug);
            
            $entityManager->flush();

            $this->addFlash('success', 'L\'Elseworld a été modifié avec succès.');
            return $this->redirectToRoute('app_elseworld_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('elseworld/edit.html.twig', [
            'elseworld' => $elseworld,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_elseworld_delete', methods: ['POST'])]
    public function delete(Request $request, Elseworld $elseworld, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$elseworld->getId(), $request->request->get('_token'))) {
            $entityManager->remove($elseworld);
            $entityManager->flush();
            $this->addFlash('success', 'L\'Elseworld a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_elseworld_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/univers/{id}/elseworlds', name: 'app_universe_elseworlds', methods: ['GET'])]
    public function universeElseworlds(Univers $univers, ElseworldRepository $elseworldRepository): Response
    {
        $elseworlds = $elseworldRepository->findByParentUniverse($univers->getId());
        
        return $this->render('elseworld/universe_elseworlds.html.twig', [
            'elseworlds' => $elseworlds,
            'universe' => $univers
        ]);
    }
} 