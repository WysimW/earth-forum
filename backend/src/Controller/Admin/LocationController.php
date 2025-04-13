<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/location')]
class LocationController extends AbstractController
{
    #[Route('/', name: 'admin_location_index', methods: ['GET'])]
    public function index(LocationRepository $locationRepository): Response
    {
        return $this->render('admin/location/index.html.twig', [
            'locations' => $locationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_location_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $location = new Location();
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir la date de création
           // $location->setCreatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($location);
            $entityManager->flush();

            $this->addFlash('success', 'Le lieu a été créé avec succès.');

            return $this->redirectToRoute('admin_location_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/location/new.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_location_show', methods: ['GET'])]
    public function show(Location $location): Response
    {
        return $this->render('admin/location/show.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_location_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour la date de modification
           // $location->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->flush();

            $this->addFlash('success', 'Le lieu a été modifié avec succès.');

            return $this->redirectToRoute('admin_location_show', ['id' => $location->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/location/edit.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_location_delete', methods: ['POST'])]
    public function delete(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$location->getId(), $request->request->get('_token'))) {
            $entityManager->remove($location);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le lieu a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_location_index', [], Response::HTTP_SEE_OTHER);
    }
}