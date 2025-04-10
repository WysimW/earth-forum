<?php

namespace App\Controller\Admin;

use App\Entity\Forum;
use App\Form\ForumType;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/forums', name: 'admin_forum_')]
class AdminForumController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ForumRepository $forumRepository): Response
    {
        $forums = $forumRepository->findAll();

        return $this->render('admin/forum/index.html.twig', [
            'forums' => $forums
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $forum = new Forum();
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification des relations : un forum ne peut pas être à la fois dans une catégorie et être un sous-forum
            if ($forum->getCategory() !== null && $forum->getParent() !== null) {
                $this->addFlash('error', 'Un forum ne peut pas être à la fois dans une catégorie et être un sous-forum.');
                return $this->render('admin/forum/new.html.twig', [
                    'forum' => $forum,
                    'form' => $form->createView()
                ]);
            }

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $forum->getName()), '-'));
            $forum->setSlug($slug);
            
            $entityManager->persist($forum);
            $entityManager->flush();

            $this->addFlash('success', 'Le forum a été créé avec succès.');
            return $this->redirectToRoute('admin_forum_index');
        }

        return $this->render('admin/forum/new.html.twig', [
            'forum' => $forum,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Forum $forum): Response
    {
        return $this->render('admin/forum/show.html.twig', [
            'forum' => $forum
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Forum $forum, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification des relations : un forum ne peut pas être à la fois dans une catégorie et être un sous-forum
            if ($forum->getCategory() !== null && $forum->getParent() !== null) {
                $this->addFlash('error', 'Un forum ne peut pas être à la fois dans une catégorie et être un sous-forum.');
                return $this->render('admin/forum/edit.html.twig', [
                    'forum' => $forum,
                    'form' => $form->createView()
                ]);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Le forum a été modifié avec succès.');
            return $this->redirectToRoute('admin_forum_index');
        }

        return $this->render('admin/forum/edit.html.twig', [
            'forum' => $forum,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Forum $forum, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$forum->getId(), $request->request->get('_token'))) {
            // Vérifier si le forum a des threads ou des sous-forums
            if (count($forum->getThreads()) > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce forum car il contient des threads.');
                return $this->redirectToRoute('admin_forum_index');
            }
            
            if (count($forum->getSubForums()) > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce forum car il contient des sous-forums.');
                return $this->redirectToRoute('admin_forum_index');
            }
            
            $entityManager->remove($forum);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le forum a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_forum_index');
    }
}