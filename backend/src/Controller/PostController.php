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
    public function new(Request $request, int $threadId, EntityManagerInterface $entityManager, CharacterRepository $characterRepository): Response
    {
        $thread = $entityManager->getRepository(Thread::class)->find($threadId);
        
        if (!$thread) {
            throw $this->createNotFoundException('Thread not found');
        }
        
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());

        // Check if thread is closed
        if (!$thread->isOpen() && !$this->isGranted('ROLE_MODERATOR')) {
            $this->addFlash('error', 'Cette discussion est fermée.');
            return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
        }

        // Check if thread is a roleplay type and if so, use the appropriate form
        $isRpThread = $thread->getType() === 'roleplay';
        
        if ($isRpThread) {
            // For roleplay threads, get valid characters for the current user
            if ($thread->isOpen()) {
                $userCharacters = $characterRepository->findValidatedCharactersForUser($this->getUser());
            } else {
                // If the thread is closed, only participants can post
                $userCharacters = $characterRepository->findValidatedParticipantsForUser($this->getUser(), $thread);
            }
            
            // Create the roleplay form type with character selection
            $form = $this->createForm(PostRoleplayType::class, $post, [
                'characters' => $userCharacters,
            ]);
        } else {
            // For regular threads, use standard post form
            $form = $this->createForm(PostType::class, $post, [
                'is_roleplay' => $thread->getType() === 'roleplay'
            ]);
        }
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Pour les brouillons
            $isDraft = $request->request->get('save_draft') !== null;
            $post->setIsDraft($isDraft);
            
            // Définir le type de post en fonction du type de thread
            if ($thread->getType() === 'roleplay') {
                $post->setType('roleplay'); // in-character pour les threads RP
            } else {
                $post->setType('normal'); // normal pour les autres threads
            }
            
            // For roleplay threads, add character as participant if not already in the list
            if ($thread->getType() === 'roleplay' && $post->getCharacter() && !$thread->getParticipants()->contains($post->getCharacter())) {
                if ($thread->isFull()) {
                    $this->addFlash('error', 'Cette scène RP a atteint son nombre maximum de participants.');
                    return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
                }
                
                $thread->addParticipant($post->getCharacter());
            }
            
            // Update thread's lastPostAt date
            $thread->setUpdatedAt(new \DateTimeImmutable());
            
            // Si un post est cité, on ajoute une mention dans le contenu
            if ($post->getQuotedPost()) {
                $quotedContent = $post->getQuotedPost()->getContent();
                $quotedAuthor = $post->getQuotedPost()->getAuthor()->getPseudo();
                $post->setContent(sprintf('[quote="%s"]%s[/quote]%s%s', 
                    $quotedAuthor, 
                    $quotedContent, 
                    "\n\n", 
                    $post->getContent()
                ));
            }
            
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            
            if ($isDraft) {
                $this->addFlash('success', 'Votre brouillon a été enregistré avec succès.');
            } else {
                $this->addFlash('success', 'Votre message a été publié avec succès.');
            }
            
            return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
        }
        
        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
            'thread' => $thread,
            'isRoleplay' => $isRpThread,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                $thread->getForum()->getName() => $this->generateUrl('app_forum_show', ['id' => $thread->getForum()->getId()]),
                $thread->getTitle() => $this->generateUrl('app_thread_show', ['id' => $thread->getId()]),
                'Nouveau message' => $this->generateUrl('app_post_new', ['threadId' => $thread->getId()]),
            ]),
        ]);
    }

    #[Route('/thread/{threadId}/post/quick-reply', name: 'app_post_quick_reply', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function quickReply(Request $request, int $threadId, EntityManagerInterface $entityManager, CharacterRepository $characterRepository): Response
    {
        $thread = $entityManager->getRepository(Thread::class)->find($threadId);
        
        if (!$thread) {
            throw $this->createNotFoundException('Thread not found');
        }
        
        // Check if thread is closed
        if (!$thread->isOpen() && !$this->isGranted('ROLE_MODERATOR')) {
            return $this->json(['success' => false, 'message' => 'Cette discussion est fermée.'], 403);
        }
        
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());
        $post->setContent($request->request->get('content'));
        
        // Pour les threads RP
        if ($thread->isRoleplay()) {
            // Définir le type de post comme roleplay
            $post->setType('roleplay'); // Par défaut, un post dans un thread RP est "in-character"
            
            $characterId = $request->request->get('character_id');
            
            if (!$characterId) {
                return $this->json(['success' => false, 'message' => 'Veuillez sélectionner un personnage.'], 400);
            }
            
            // Vérifier que le personnage appartient à l'utilisateur et est validé
            $character = $characterRepository->findOneBy([
                'id' => $characterId,
                'user' => $this->getUser(),
                'status' => 'validated'
            ]);
            
            if (!$character) {
                return $this->json(['success' => false, 'message' => 'Personnage invalide ou non autorisé.'], 400);
            }
            
            $post->setCharacter($character);
            
            // Ajouter le personnage comme participant s'il n'y est pas déjà
            if (!$thread->getParticipants()->contains($character)) {
                if ($thread->isFull()) {
                    return $this->json(['success' => false, 'message' => 'Cette scène RP a atteint son nombre maximum de participants.'], 400);
                }
                
                $thread->addParticipant($character);
            }
        }
        
        // Pour les brouillons
        $isDraft = $request->request->get('is_draft') === '1';
        $post->setIsDraft($isDraft);
        
        // Mise à jour de la date de dernière activité du thread
        $thread->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($post);
        $this->entityManager->flush();
        
        // Si c'est une requête AJAX, renvoyer une réponse JSON
        if ($request->isXmlHttpRequest()) {
            $message = $isDraft ? 'Brouillon enregistré.' : 'Message publié.';
            
            return $this->json([
                'success' => true, 
                'message' => $message,
                'postId' => $post->getId(),
                'isDraft' => $isDraft
            ]);
        }
        
        // Sinon, redirection classique
        $message = $isDraft ? 'Votre brouillon a été enregistré avec succès.' : 'Votre message a été publié avec succès.';
        $this->addFlash('success', $message);
        
        return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Post $post, CharacterRepository $characterRepository): Response
    {
        if ($post->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas éditer ce message.');
        }

        $thread = $post->getThread();
        $isRpThread = $thread->getType() === 'roleplay';

        // Assurez-vous que le type de post est correctement défini pour les threads RP
        if ($isRpThread) {
            // Définir le type de post comme roleplay si ce n'est pas déjà le cas
            if ($post->getType() !== 'roleplay' && $post->getType() !== 'ooc') {
                $post->setType('roleplay');
            }
            
            $userCharacters = $characterRepository->findValidatedParticipantsForUser($this->getUser(), $thread);
            $form = $this->createForm(PostRoleplayType::class, $post, [
                'characters' => $userCharacters,
            ]);
        } else {
            $form = $this->createForm(PostType::class, $post);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Pour les brouillons
            $isDraft = $request->request->get('save_draft') !== null;
            $post->setIsDraft($isDraft);
            
            $post->setEditedAt(new \DateTime());
            $this->entityManager->flush();
            
            if ($isDraft) {
                $this->addFlash('success', 'Votre brouillon a été mis à jour avec succès.');
            } else {
                $this->addFlash('success', 'Votre message a été mis à jour avec succès.');
            }
            
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

    #[Route('/post/{id}/publish', name: 'app_post_publish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publish(Request $request, Post $post): Response
    {
        if ($post->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas publier ce message.');
        }
        
        if (!$post->isDraft()) {
            $this->addFlash('error', 'Ce message n\'est pas un brouillon.');
            return $this->redirectToRoute('app_thread_show', ['id' => $post->getThread()->getId()]);
        }
        
        // Publier le brouillon
        $post->setIsDraft(false);
        
        // Mise à jour de la date de dernière activité du thread
        $post->getThread()->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Votre message a été publié avec succès.');
        
        return $this->redirectToRoute('app_thread_show', ['id' => $post->getThread()->getId()]);
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

    #[Route('/my-drafts', name: 'app_my_drafts')]
    #[IsGranted('ROLE_USER')]
    public function myDrafts(): Response
    {
        // Récupérer les brouillons de messages
        $drafts = $this->entityManager->getRepository(Post::class)->findBy([
            'author' => $this->getUser(),
            'isDraft' => true
        ], ['updatedAt' => 'DESC']);
        
        // Récupérer les brouillons de discussions
        $threadDrafts = $this->entityManager->getRepository(Thread::class)->findBy([
            'author' => $this->getUser(),
            'isDraft' => true
        ], ['updatedAt' => 'DESC']);
        
        return $this->render('post/my_drafts.html.twig', [
            'drafts' => $drafts,
            'threadDrafts' => $threadDrafts,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes brouillons' => $this->generateUrl('app_my_drafts'),
            ]),
        ]);
    }

    #[Route('/post/new-draft', name: 'app_post_new_draft')]
    #[IsGranted('ROLE_USER')]
    public function newStandaloneDraft(Request $request): Response
    {
        $post = new Post();
        $post->setAuthor($this->getUser());
        $post->setIsDraft(true);
        
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre brouillon a été enregistré.');
            
            return $this->redirectToRoute('app_my_drafts');
        }
        
        return $this->render('post/new_draft.html.twig', [
            'form' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes brouillons' => $this->generateUrl('app_my_drafts'),
                'Nouveau brouillon' => $this->generateUrl('app_post_new_draft'),
            ]),
        ]);
    }

    #[Route('/thread/{threadId}/reply-form', name: 'app_post_reply_form')]
    public function replyForm(int $threadId, CharacterRepository $characterRepository): Response
    {
        $thread = $this->entityManager->getRepository(Thread::class)->find($threadId);
        
        if (!$thread) {
            throw $this->createNotFoundException('Thread not found');
        }
        
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());
        $post->setType('roleplay'); // Par défaut, un post dans un thread RP est "in-character"
        
        // Récupérer les personnages validés de l'utilisateur
        $userCharacters = $characterRepository->findValidatedCharactersForUser($this->getUser());
        
        $form = $this->createForm(PostRoleplayType::class, $post, [
            'characters' => $userCharacters,
            'action' => $this->generateUrl('app_post_new', ['threadId' => $threadId])
        ]);
        
        return $this->render('post/_reply_form.html.twig', [
            'form' => $form->createView(),
            'thread' => $thread
        ]);
    }
    
    #[Route('/thread/{threadId}/reply-hrp-form', name: 'app_post_reply_hrp_form')]
    public function replyHrpForm(int $threadId): Response
    {
        $thread = $this->entityManager->getRepository(Thread::class)->find($threadId);
        
        if (!$thread) {
            throw $this->createNotFoundException('Thread not found');
        }
        
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());
        $post->setType('ooc'); // Hors-roleplay
        
        $form = $this->createForm(PostType::class, $post, [
            'action' => $this->generateUrl('app_post_new', ['threadId' => $threadId])
        ]);
        
        return $this->render('post/_reply_form.html.twig', [
            'form' => $form->createView(),
            'thread' => $thread
        ]);
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