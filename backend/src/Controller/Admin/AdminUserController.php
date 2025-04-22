<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/users', name: 'admin_user_')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_creation' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer le mot de passe
            $plainPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été modifié avec succès.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/password', name: 'change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createFormBuilder()
            ->add('password', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'attr' => ['class' => 'form-control']
            ])
            ->add('confirmPassword', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, [
                'label' => 'Confirmer le mot de passe',
                'attr' => ['class' => 'form-control']
            ])
            ->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            if ($data['password'] !== $data['confirmPassword']) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('admin_user_change_password', ['id' => $user->getId()]);
            }
            
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            
            $entityManager->flush();

            $this->addFlash('success', 'Le mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/change_password.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Vérification des relations avant suppression
            if (count($user->getThreads()) > 0 || count($user->getPosts()) > 0) {
                $this->addFlash('error', 'Impossible de supprimer cet utilisateur car il a du contenu associé (threads ou posts).');
                return $this->redirectToRoute('admin_user_index');
            }
            
            $entityManager->remove($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/toggle-faction-permission', name: 'toggle_faction_permission', methods: ['POST'])]
    public function toggleFactionPermission(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_faction_permission'.$user->getId(), $request->request->get('_token'))) {
            $user->setCanCreateFaction(!$user->canCreateFaction());
            $entityManager->flush();
            
            $status = $user->canCreateFaction() ? 'accordée' : 'retirée';
            $this->addFlash('success', 'La permission de créer une faction a été ' . $status . ' à ' . $user->getPseudo());
        }
        
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }
    
    #[Route('/{id}/toggle-rp-forum-permission', name: 'toggle_rp_forum_permission', methods: ['POST'])]
    public function toggleRpForumPermission(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_rp_forum_permission'.$user->getId(), $request->request->get('_token'))) {
            $user->setCanCreateRpForum(!$user->canCreateRpForum());
            $entityManager->flush();
            
            $status = $user->canCreateRpForum() ? 'accordée' : 'retirée';
            $this->addFlash('success', 'La permission de créer un forum RP a été ' . $status . ' à ' . $user->getPseudo());
        }
        
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }
}