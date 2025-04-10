<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/roles', name: 'admin_role_')]
class AdminRoleController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(RoleRepository $roleRepository): Response
    {
        $roles = $roleRepository->findAll();

        return $this->render('admin/role/index.html.twig', [
            'roles' => $roles
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($role);
            $entityManager->flush();

            $this->addFlash('success', 'Le rôle a été créé avec succès.');
            return $this->redirectToRoute('admin_role_index');
        }

        return $this->render('admin/role/new.html.twig', [
            'role' => $role,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Role $role): Response
    {
        return $this->render('admin/role/show.html.twig', [
            'role' => $role
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Role $role, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le rôle a été modifié avec succès.');
            return $this->redirectToRoute('admin_role_index');
        }

        return $this->render('admin/role/edit.html.twig', [
            'role' => $role,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Role $role, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$role->getId(), $request->request->get('_token'))) {
            // Vérifier si le rôle est utilisé par des utilisateurs
            if (count($role->getUsers()) > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce rôle car il est attribué à des utilisateurs.');
                return $this->redirectToRoute('admin_role_index');
            }
            
            $entityManager->remove($role);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le rôle a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_role_index');
    }
}