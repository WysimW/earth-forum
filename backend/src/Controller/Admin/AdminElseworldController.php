<?php

namespace App\Controller\Admin;

use App\Entity\Elseworld;
use App\Form\ElseworldType;
use App\Repository\ElseworldRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/elseworlds')]
#[IsGranted('ROLE_ADMIN')]
class AdminElseworldController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }

    #[Route('/', name: 'admin_elseworld_index', methods: ['GET'])]
    public function index(ElseworldRepository $elseworldRepository): Response
    {
        return $this->render('admin/elseworld/index.html.twig', [
            'elseworlds' => $elseworldRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_elseworld_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $elseworld = new Elseworld();
        $form = $this->createForm(ElseworldType::class, $elseworld);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $this->slugger->slug(strtolower($elseworld->getName()));
            $elseworld->setSlug($slug);
            
            $this->entityManager->persist($elseworld);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'Elseworld a été créé avec succès.');
            return $this->redirectToRoute('admin_elseworld_index');
        }

        return $this->render('admin/elseworld/new.html.twig', [
            'elseworld' => $elseworld,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_elseworld_show', methods: ['GET'])]
    public function show(Elseworld $elseworld): Response
    {
        return $this->render('admin/elseworld/show.html.twig', [
            'elseworld' => $elseworld,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_elseworld_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Elseworld $elseworld): Response
    {
        $form = $this->createForm(ElseworldType::class, $elseworld);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $this->slugger->slug(strtolower($elseworld->getName()));
            $elseworld->setSlug($slug);
            
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'Elseworld a été mis à jour avec succès.');
            return $this->redirectToRoute('admin_elseworld_index');
        }

        return $this->render('admin/elseworld/edit.html.twig', [
            'elseworld' => $elseworld,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_elseworld_delete', methods: ['POST'])]
    public function delete(Request $request, Elseworld $elseworld): Response
    {
        if ($this->isCsrfTokenValid('delete'.$elseworld->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($elseworld);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'Elseworld a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_elseworld_index');
    }
} 