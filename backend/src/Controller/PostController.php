<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Thread;
use App\Form\PostRoleplayType;
use App\Form\PostType;
use App\Repository\CharacterRepository;
use App\Service\BreadcrumbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BreadcrumbService $breadcrumbService;

    public function __construct(
        EntityManagerInterface $entityManager,
        BreadcrumbService $breadcrumbService
    ) {
        $this->entityManager = $entityManager;
        $this->breadcrumbService = $breadcrumbService;
    }

    #[Route('/thread/{threadId}/post/new', name: 'app_post_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, Thread $thread, CharacterRepository $characterRepository): Response
    {
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());

        $isRpThread = $thread->getType() === 'roleplay';
        
        if ($isRpThread) {
            if ($thread->isOpen()) {
                $userCharacters = $characterRepository->findBy(['user' => $this->getUser()]);
            } else {
                $userCharacters = $characterRepository->findParticipantsForUser($this->getUser(), $thread);
            }
            
            $form = $this->createForm(PostRoleplayType::class, $post, [
                'characters' => $userCharacters,
            ]);
        } else {
            $form = $this->createForm(PostType::class, $post);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isRpThread && $post->getCharacter() && !$thread->getParticipants()->contains($post->getCharacter())) {
                if ($thread->isFull()) {
                    $this->addFlash('error', 'Cette scène RP a atteint son nombre maximum de participants.');
                    return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
                }
                
                $thread->addParticipant($post->getCharacter());
            }

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
        }

        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
            'thread' => $thread,
            'isRoleplay' => $isRpThread,
            'breadcrumbs' => $this->getBreadcrumbsForThread($thread, ['Nouvelle réponse' => null]),
        ]);
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Post $post, CharacterRepository $characterRepository): Response
    {
        if ($post->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas éditer ce message.');
        }

        $thread = $post->getThread();
        $isRpThread = $thread->getType() === 'roleplay';

        if ($isRpThread) {
            $userCharacters = $characterRepository->findParticipantsForUser($this->getUser(), $thread);
            $form = $this->createForm(PostRoleplayType::class, $post, [
                'characters' => $userCharacters,
            ]);
        } else {
            $form = $this->createForm(PostType::class, $post);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre message a été mis à jour avec succès.');
            
            return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
            'thread' => $thread,
            'isRoleplay' => $isRpThread,
            'breadcrumbs' => $this->getBreadcrumbsForThread($thread, ['Éditer le message' => null]),
        ]);
    }

    #[Route('/post/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Post $post): Response
    {
        $thread = $post->getThread();

        if ($post->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce message.');
        }

        if ($this->isCsrfTokenValid('delete_post_' . $post->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le message a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
    }

    private function getBreadcrumbsForThread(Thread $thread, array $additional = []): array
    {
        $forum = $thread->getForum();
        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_roleplay')
        ];
        
        $currentForum = $forum;
        $parentForums = [];
        
        while ($parent = $currentForum->getParent()) {
            $parentForums[] = $parent;
            $currentForum = $parent;
        }
        
        $parentForums = array_reverse($parentForums);
        
        foreach ($parentForums as $parentForum) {
            $breadcrumbs[$parentForum->getName()] = $this->generateUrl('app_forum_show', ['id' => $parentForum->getId()]);
        }
        
        $breadcrumbs[$forum->getName()] = $this->generateUrl('app_forum_show', ['id' => $forum->getId()]);
        $breadcrumbs[$thread->getTitle()] = $this->generateUrl('app_thread_show', ['id' => $thread->getId()]);
        
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->breadcrumbService->generate($breadcrumbs);
    }
}