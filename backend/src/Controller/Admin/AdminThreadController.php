<?php

namespace App\Controller\Admin;

use App\Entity\Thread;
use App\Form\ThreadType;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/threads', name: 'admin_thread_')]
class AdminThreadController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ThreadRepository $threadRepository): Response
    {
        $threads = $threadRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/thread/index.html.twig', [
            'threads' => $threads
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $thread = new Thread();
        $form = $this->createForm(ThreadType::class, $thread);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thread->setCreatedAt(new \DateTimeImmutable());
            // Commentaire temporaire en attendant la mise à jour de la base de données
            // $thread->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($thread);
            $entityManager->flush();

            $this->addFlash('success', 'Le thread a été créé avec succès.');
            return $this->redirectToRoute('admin_thread_index');
        }

        return $this->render('admin/thread/new.html.twig', [
            'thread' => $thread,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Thread $thread): Response
    {
        return $this->render('admin/thread/show.html.twig', [
            'thread' => $thread
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Thread $thread, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ThreadType::class, $thread);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Commentaire temporaire en attendant la mise à jour de la base de données
            // $thread->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->flush();

            $this->addFlash('success', 'Le thread a été modifié avec succès.');
            return $this->redirectToRoute('admin_thread_index');
        }

        return $this->render('admin/thread/edit.html.twig', [
            'thread' => $thread,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Thread $thread, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$thread->getId(), $request->request->get('_token'))) {
            // Vérifier si le thread a des posts
            if (count($thread->getPosts()) > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce thread car il contient des messages.');
                return $this->redirectToRoute('admin_thread_index');
            }
            
            $entityManager->remove($thread);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le thread a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_thread_index');
    }
}