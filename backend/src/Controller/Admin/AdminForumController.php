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
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin/forums', name: 'admin_forum_')]
class AdminForumController extends AbstractController
{
    public function __construct(
        private ForumRepository $forumRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

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
            // Supprimer tous les posts des threads du forum et de ses sous-forums
            $this->deleteAllPosts($forum, $entityManager);
            
            // Supprimer tous les threads du forum et de ses sous-forums
            $this->deleteAllThreads($forum, $entityManager);
            
            // Supprimer les sous-forums
            $this->deleteSubforums($forum, $entityManager);
            
            // Supprimer le forum lui-même
            $entityManager->remove($forum);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le forum et tous ses contenus ont été supprimés avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_forum_index');
    }

    private function deleteAllPosts(Forum $forum, EntityManagerInterface $entityManager): void
    {
        // Supprimer les posts des threads du forum
        foreach ($forum->getThreads() as $thread) {
            foreach ($thread->getPosts() as $post) {
                $entityManager->remove($post);
            }
        }

        // Supprimer les posts des sous-forums
        foreach ($forum->getSubforums() as $subforum) {
            $this->deleteAllPosts($subforum, $entityManager);
        }
    }

    private function deleteAllThreads(Forum $forum, EntityManagerInterface $entityManager): void
    {
        // Supprimer les threads du forum
        foreach ($forum->getThreads() as $thread) {
            $entityManager->remove($thread);
        }

        // Supprimer les threads des sous-forums
        foreach ($forum->getSubforums() as $subforum) {
            $this->deleteAllThreads($subforum, $entityManager);
        }
    }

    private function deleteSubforums(Forum $forum, EntityManagerInterface $entityManager): void
    {
        // Supprimer les sous-forums
        foreach ($forum->getSubforums() as $subforum) {
            $entityManager->remove($subforum);
        }
    }

    #[Route('/admin/forums/positions', name: 'positions')]
    public function positions(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parentForums = $this->forumRepository->findBy(['parent' => null], ['position' => 'ASC']);
        
        return $this->render('admin/forum/positions.html.twig', [
            'parentForums' => $parentForums,
        ]);
    }

    #[Route('/admin/forums/update-positions', name: 'update_positions', methods: ['POST'])]
    public function updatePositions(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        try {
            foreach ($data as $item) {
                $forum = $this->forumRepository->find($item['id']);
                if ($forum) {
                    $forum->setPosition($item['position']);
                    if (isset($item['parentId'])) {
                        $parent = $this->forumRepository->find($item['parentId']);
                        $forum->setParent($parent);
                    } else {
                        $forum->setParent(null);
                    }
                }
            }
            
            $entityManager->flush();
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}