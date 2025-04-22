<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Forum;
use App\Entity\Faction;
use App\Entity\Univers;
use App\Entity\Location;
use App\Form\FactionType;
use App\Repository\UserRepository;
use App\Repository\FactionRepository;
use App\Repository\UniversRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/faction')]
class AdminFactionController extends AbstractController
{
    #[Route('/', name: 'admin_faction_index', methods: ['GET'])]
    public function index(FactionRepository $factionRepository): Response
    {
        return $this->render('admin/faction/index.html.twig', [
            'factions' => $factionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_faction_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UniversRepository $universRepository): Response
    {
        $faction = new Faction();
        $form = $this->createForm(FactionType::class, $faction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($faction);
            $entityManager->flush();

            $this->addFlash('success', 'La faction a été créée avec succès.');
            return $this->redirectToRoute('admin_faction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/faction/new.html.twig', [
            'faction' => $faction,
            'form' => $form,
            'universes' => $universRepository->findAll(),
        ]);
    }

    #[Route('/by-universe', name: 'admin_faction_by_universe', methods: ['GET'])]
    public function byUniverse(UniversRepository $universRepository): Response
    {
        $universes = $universRepository->findAll();
        
        return $this->render('admin/faction/by_universe.html.twig', [
            'universes' => $universes,
        ]);
    }

    #[Route('/users-permissions', name: 'admin_faction_permissions', methods: ['GET'])]
    public function permissions(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        
        return $this->render('admin/faction/permissions.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}', name: 'admin_faction_show', methods: ['GET'])]
    public function show(Faction $faction): Response
    {
        return $this->render('admin/faction/show.html.twig', [
            'faction' => $faction,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_faction_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Faction $faction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FactionType::class, $faction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La faction a été mise à jour avec succès.');
            return $this->redirectToRoute('admin_faction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/faction/edit.html.twig', [
            'faction' => $faction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_faction_delete', methods: ['POST'])]
    public function delete(Request $request, Faction $faction, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$faction->getId(), $request->request->get('_token'))) {
            $entityManager->remove($faction);
            $entityManager->flush();
            $this->addFlash('success', 'La faction a été supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_faction_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/toggle-permission/{id}', name: 'admin_faction_toggle_permission', methods: ['POST'])]
    public function togglePermission(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_permission'.$user->getId(), $request->request->get('_token'))) {
            // Si on donne la permission, vérifier si on doit aussi donner la permission forum RP
            $giveRpForumPermission = $request->request->getBoolean('give_rp_forum_permission', false);
            
            // Inverser la permission de création de faction
            $newPermissionValue = !$user->canCreateFaction();
            $user->setCanCreateFaction($newPermissionValue);
            
            // Si on accorde la permission de faction ET que l'option forum RP est cochée
            if ($newPermissionValue && $giveRpForumPermission) {
                $user->setCanCreateRpForum(true);
            }
            // Si on retire la permission de faction, retirer aussi la permission de forum RP
            elseif (!$newPermissionValue) {
                $user->setCanCreateRpForum(false);
            }
            
            $entityManager->flush();
            
            $status = $user->canCreateFaction() ? 'accordée' : 'retirée';
            $message = "La permission de créer des factions a été {$status} à {$user->getPseudo()}.";
            
            if ($user->canCreateFaction() && $user->canCreateRpForum()) {
                $message .= " La permission de créer un forum RP a également été accordée.";
            }
            
            $this->addFlash('success', $message);
        }
        
        return $this->redirectToRoute('admin_faction_permissions', [], Response::HTTP_SEE_OTHER);
    }
} 