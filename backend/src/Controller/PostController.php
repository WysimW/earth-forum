<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\Npc;
use App\Form\PostRoleplayType;
use App\Form\PostType;
use App\Repository\CharacterRepository;
use App\Repository\ReadPostRepository;
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
    private ReadPostRepository $readPostRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        BreadcrumbService $breadcrumbService,
        ReadPostRepository $readPostRepository
    ) {
        $this->entityManager = $entityManager;
        $this->breadcrumbService = $breadcrumbService;
        $this->readPostRepository = $readPostRepository;
    }

    #[Route('/univers/{universeSlug}/thread/{threadId}/post/new', name: 'app_post_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, string $universeSlug, int $threadId, EntityManagerInterface $entityManager, CharacterRepository $characterRepository): Response
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
            return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
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
            
            // Récupérer les PNJ validés de l'utilisateur
            $availableNpcs = $entityManager->getRepository(Npc::class)->findBy([
                'user' => $this->getUser(),
                'status' => 'validated'
            ]);
            
            // Create the roleplay form type with character selection
            $form = $this->createForm(PostRoleplayType::class, $post, [
                'characters' => $userCharacters,
                'npcs' => $availableNpcs
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
            
            // Gérer les PNJ sélectionnés
            if ($thread->isRoleplay() && $form->has('npcs')) {
                $selectedNpcs = $form->get('npcs')->getData();
                foreach ($selectedNpcs as $npc) {
                    $post->addNpc($npc);
                }
            }
            
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
                    return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
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
            
            // Marquer le post comme lu pour l'auteur
            if (!$isDraft) {
                $this->readPostRepository->markAsRead($this->getUser(), $post);
            }
            
            if ($isDraft) {
                $this->addFlash('success', 'Votre brouillon a été enregistré avec succès.');
            } else {
                $this->addFlash('success', 'Votre message a été publié avec succès.');
            }
            
            return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
        }
        
        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
            'thread' => $thread,
            'isRoleplay' => $isRpThread,
            'univers' => $thread->getForum()->getUniverse(),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                $thread->getForum()->getName() => $this->generateUrl('app_forum_show', [
                    'universeSlug' => $universeSlug,
                    'id' => $thread->getForum()->getId()
                ]),
                $thread->getTitle() => $this->generateUrl('app_thread_show', [
                    'universeSlug' => $universeSlug,
                    'id' => $thread->getId()
                ]),
                'Nouveau message' => $this->generateUrl('app_post_new', [
                    'universeSlug' => $universeSlug,
                    'threadId' => $thread->getId()
                ]),
            ]),
        ]);
    }

    #[Route('/univers/{universeSlug}/thread/{threadId}/post/quick-reply', name: 'app_post_quick_reply', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function quickReply(Request $request, string $universeSlug, int $threadId, EntityManagerInterface $entityManager, CharacterRepository $characterRepository): Response
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
            
            // Gérer les PNJ sélectionnés
            $npcIds = $request->request->all('npc_ids');
            if (!empty($npcIds)) {
                foreach ($npcIds as $npcId) {
                    $npc = $entityManager->getRepository(Npc::class)->find($npcId);
                    if ($npc && $thread->getNpcs()->contains($npc)) {
                        $post->addNpc($npc);
                    }
                }
            }
            
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
        
        // Marquer le post comme lu pour l'auteur
        if (!$isDraft) {
            $this->readPostRepository->markAsRead($this->getUser(), $post);
        }
        
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
        
        return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
    }

    #[Route('/univers/{universeSlug}/post/{id}/edit', name: 'app_post_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, string $universeSlug, Post $post, CharacterRepository $characterRepository): Response
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
            
            // Récupérer tous les personnages validés de l'utilisateur, pas seulement les participants
            $userCharacters = $characterRepository->findValidatedCharactersForUser($this->getUser());
            
            // Récupérer tous les PNJ validés de l'utilisateur
            $availableNpcs = $this->entityManager->getRepository(Npc::class)->findBy([
                'user' => $this->getUser(),
                'status' => 'validated'
            ]);
            
            $form = $this->createForm(PostRoleplayType::class, $post, [
                'characters' => $userCharacters,
                'npcs' => $availableNpcs
            ]);
        } else {
            $form = $this->createForm(PostType::class, $post);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Pour les brouillons
            $isDraft = $request->request->get('save_draft') !== null;
            $post->setIsDraft($isDraft);
            
            // Gérer les PNJ sélectionnés
            if ($thread->isRoleplay() && $form->has('npcs')) {
                // Supprimer tous les PNJ existants
                foreach ($post->getNpcs() as $npc) {
                    $post->removeNpc($npc);
                }
                
                // Ajouter les nouveaux PNJ sélectionnés
                $selectedNpcs = $form->get('npcs')->getData();
                foreach ($selectedNpcs as $npc) {
                    $post->addNpc($npc);
                }
            }
            
            // Si l'utilisateur a changé de personnage et que c'est un thread RP, ajouter le personnage comme participant
            if ($isRpThread && $post->getCharacter() && !$thread->getParticipants()->contains($post->getCharacter())) {
                if ($thread->isFull()) {
                    $this->addFlash('error', 'Cette scène RP a atteint son nombre maximum de participants.');
                    return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
                }
                
                $thread->addParticipant($post->getCharacter());
            }
            
            $post->setEditedAt(new \DateTime());
            $this->entityManager->flush();
            
            if ($isDraft) {
                $this->addFlash('success', 'Votre brouillon a été mis à jour avec succès.');
            } else {
                $this->addFlash('success', 'Votre message a été mis à jour avec succès.');
            }
            
            return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
            'thread' => $thread,
            'isRoleplay' => $isRpThread,
            'univers' => $thread->getForum()->getUniverse(),
            'breadcrumbs' => $this->getBreadcrumbsForThread($thread, $universeSlug, ['Éditer le message' => null]),
        ]);
    }

    #[Route('/univers/{universeSlug}/post/{id}/publish', name: 'app_post_publish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publish(Request $request, string $universeSlug, Post $post): Response
    {
        if ($post->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas publier ce message.');
        }
        
        if (!$post->isDraft()) {
            $this->addFlash('error', 'Ce message n\'est pas un brouillon.');
            return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $post->getThread()->getId()]);
        }
        
        // Publier le brouillon
        $post->setIsDraft(false);
        
        // Mise à jour de la date de dernière activité du thread
        $post->getThread()->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Votre message a été publié avec succès.');
        
        return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $post->getThread()->getId()]);
    }
    
    #[Route('/univers/{universeSlug}/post/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, string $universeSlug, Post $post): Response
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

        return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
    }

    #[Route('/univers/{universeSlug}/post/{id}/moderate', name: 'app_post_moderate', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function moderate(Request $request, string $universeSlug, Post $post): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('moderate_post_' . $post->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }

        $moderationAction = $request->request->get('moderation_action');
        $moderationReason = $request->request->get('moderation_reason');
        $internalNote = $request->request->get('internal_note');
        $quotedText = $request->request->get('quoted_text');

        // Pour les actions autres que 'unhide', le motif est obligatoire
        if (!$moderationAction || ($moderationAction !== 'unhide' && !$moderationReason)) {
            $this->addFlash('error', 'Tous les champs obligatoires doivent être remplis.');
            return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $post->getThread()->getId()]);
        }

        $thread = $post->getThread();
        $moderator = $this->getUser();

        // Appliquer l'action de modération
        switch ($moderationAction) {
            case 'warn':
                $this->handleWarning($post, $moderationReason, $internalNote, $moderator, $quotedText);
                $this->addFlash('success', 'Un avertissement a été envoyé à l\'auteur du message.');
                break;

            case 'edit_request':
                $this->handleEditRequest($post, $moderationReason, $internalNote, $moderator, $quotedText);
                $this->addFlash('success', 'Une demande de modification a été envoyée à l\'auteur.');
                break;

            case 'hide':
                $this->handleHidePost($post, $moderationReason, $internalNote, $moderator, $quotedText);
                $this->addFlash('success', 'Le message a été masqué.');
                break;

            case 'unhide':
                $this->handleUnhidePost($post, $moderator, $moderationReason);
                $this->addFlash('success', 'Le message a été démasqué.');
                break;

            default:
                $this->addFlash('error', 'Action de modération invalide.');
                break;
        }

        return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
    }

    #[Route('/univers/{universeSlug}/my-drafts', name: 'app_my_drafts')]
    #[IsGranted('ROLE_USER')]
    public function myDrafts(string $universeSlug): Response
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
            'univers' => $this->entityManager->getRepository(Thread::class)->findOneBy([])->getForum()->getUniverse(),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes brouillons' => $this->generateUrl('app_my_drafts', ['universeSlug' => $universeSlug]),
            ]),
        ]);
    }

    #[Route('/univers/{universeSlug}/post/new-draft', name: 'app_post_new_draft')]
    #[IsGranted('ROLE_USER')]
    public function newStandaloneDraft(Request $request, string $universeSlug): Response
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
            
            return $this->redirectToRoute('app_my_drafts', ['universeSlug' => $universeSlug]);
        }
        
        return $this->render('post/new_draft.html.twig', [
            'form' => $form->createView(),
            'univers' => $this->entityManager->getRepository(Thread::class)->findOneBy([])->getForum()->getUniverse(),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes brouillons' => $this->generateUrl('app_my_drafts', ['universeSlug' => $universeSlug]),
                'Nouveau brouillon' => $this->generateUrl('app_post_new_draft', ['universeSlug' => $universeSlug]),
            ]),
        ]);
    }

    #[Route('/univers/{universeSlug}/thread/{threadId}/reply-form', name: 'app_post_reply_form')]
    public function replyForm(string $universeSlug, int $threadId, CharacterRepository $characterRepository): Response
    {
        $thread = $this->entityManager->getRepository(Thread::class)->find($threadId);
        
        if (!$thread) {
            throw $this->createNotFoundException('Thread not found');
        }
        
        // Obtenir l'univers du thread
        $universe = $thread->getForum()->getUniverse();
        if (!$universe && $thread->getForum()->getElseworld()) {
            $universe = $thread->getForum()->getElseworld()->getParentUniverse();
        }
        
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());
        $post->setType('roleplay'); // Par défaut, un post dans un thread RP est "in-character"
        
        // Récupérer les personnages validés de l'utilisateur pour cet univers uniquement
        if ($universe) {
            $userCharacters = $characterRepository->findValidatedCharactersForUserAndUniverse($this->getUser(), $universe);
        } else {
            $userCharacters = $characterRepository->findValidatedCharactersForUser($this->getUser());
        }
        
        // Récupérer les PNJ disponibles pour l'utilisateur (créés par lui + factions de ses personnages)
        if ($universe) {
            $availableNpcs = $this->entityManager->getRepository(Npc::class)->findAvailableForUser($this->getUser(), $universe);
        } else {
            $availableNpcs = $this->entityManager->getRepository(Npc::class)->findBy([
                'user' => $this->getUser(),
                'status' => 'validated'
            ]);
        }
        
        $form = $this->createForm(PostRoleplayType::class, $post, [
            'characters' => $userCharacters,
            'npcs' => $availableNpcs,
            'action' => $this->generateUrl('app_post_new', ['universeSlug' => $universeSlug, 'threadId' => $threadId])
        ]);
        
        return $this->render('post/_reply_form.html.twig', [
            'form' => $form->createView(),
            'thread' => $thread,
            'characters' => $userCharacters,
            'npcs' => $availableNpcs,
            'universe' => $universe
        ]);
    }
    
    #[Route('/univers/{universeSlug}/thread/{threadId}/reply-hrp-form', name: 'app_post_reply_hrp_form')]
    public function replyHrpForm(string $universeSlug, int $threadId): Response
    {
        $thread = $this->entityManager->getRepository(Thread::class)->find($threadId);
        
        if (!$thread) {
            throw $this->createNotFoundException('Thread not found');
        }
        
        // Obtenir l'univers du thread
        $universe = $thread->getForum()->getUniverse();
        if (!$universe && $thread->getForum()->getElseworld()) {
            $universe = $thread->getForum()->getElseworld()->getParentUniverse();
        }
        
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());
        $post->setType('ooc'); // Hors-roleplay
        
        $form = $this->createForm(PostType::class, $post, [
            'action' => $this->generateUrl('app_post_new', ['universeSlug' => $universeSlug, 'threadId' => $threadId])
        ]);
        
        return $this->render('post/_reply_form_hrp.html.twig', [
            'form' => $form->createView(),
            'thread' => $thread,
            'universe' => $universe
        ]);
    }

    private function getBreadcrumbsForThread(Thread $thread, string $universeSlug, array $additional = []): array
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
            $breadcrumbs[$parentForum->getName()] = $this->generateUrl('app_forum_show', [
                'universeSlug' => $universeSlug,
                'id' => $parentForum->getId()
            ]);
        }
        
        $breadcrumbs[$forum->getName()] = $this->generateUrl('app_forum_show', [
            'universeSlug' => $universeSlug,
            'id' => $forum->getId()
        ]);
        $breadcrumbs[$thread->getTitle()] = $this->generateUrl('app_thread_show', [
            'universeSlug' => $universeSlug,
            'id' => $thread->getId()
        ]);
        
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->breadcrumbService->generate($breadcrumbs);
    }

    private function handleWarning(Post $post, string $reason, ?string $internalNote, $moderator, ?string $quotedText = null): void
    {
        // Créer un message d'avertissement dans le thread
        $warningPost = new Post();
        $warningPost->setThread($post->getThread());
        $warningPost->setAuthor($moderator);
        $warningPost->setType('moderation');
        
        $quotedSection = '';
        if ($quotedText && !empty(trim($quotedText))) {
            $quotedSection = sprintf(
                '<div class="mt-2">
                    <strong>Extrait concerné :</strong>
                    <blockquote class="blockquote-sm border-start border-warning border-3 ps-3 mt-1">
                        <em>%s</em>
                    </blockquote>
                </div>',
                htmlspecialchars($quotedText)
            );
        }
        
        $warningContent = sprintf(
            '<div class="alert alert-warning moderation-notice">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Avertissement de modération</strong>
                </div>
                <p class="mb-1"><strong>Concernant le message :</strong> 
                    <span class="text-primary">
                        <i class="fas fa-link me-1"></i>Message #%d
                    </span> de %s
                </p>
                %s
                <p class="mb-1"><strong>Motif :</strong> %s</p>
                <small class="text-muted">Modéré par %s le %s</small>
            </div>',
            $post->getId(),
            $post->getAuthor()->getPseudo(),
            $quotedSection,
            htmlspecialchars($reason),
            $moderator->getPseudo(),
            (new \DateTime())->format('d/m/Y à H:i')
        );
        
        $warningPost->setContent($warningContent);
        
        $this->entityManager->persist($warningPost);
        
        // Ajouter une note interne si fournie
        if ($internalNote) {
            // TODO: Implémenter le système de notes internes de modération
        }
        
        $this->entityManager->flush();
    }

    private function handleEditRequest(Post $post, string $reason, ?string $internalNote, $moderator, ?string $quotedText = null): void
    {
        // Créer un message de demande de modification dans le thread
        $editRequestPost = new Post();
        $editRequestPost->setThread($post->getThread());
        $editRequestPost->setAuthor($moderator);
        $editRequestPost->setType('moderation');
        
        $quotedSection = '';
        if ($quotedText && !empty(trim($quotedText))) {
            $quotedSection = sprintf(
                '<div class="mt-2">
                    <strong>Extrait à modifier :</strong>
                    <blockquote class="blockquote-sm border-start border-info border-3 ps-3 mt-1">
                        <em>%s</em>
                    </blockquote>
                </div>',
                htmlspecialchars($quotedText)
            );
        }
        
        $editRequestContent = sprintf(
            '<div class="alert alert-info moderation-notice">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-edit me-2"></i>
                    <strong>Demande de modification</strong>
                </div>
                <p class="mb-1"><strong>Concernant le message :</strong> 
                    <span class="text-primary">
                        <i class="fas fa-link me-1"></i>Message #%d
                    </span> de %s
                </p>
                %s
                <p class="mb-1"><strong>Modifications demandées :</strong> %s</p>
                <p class="mb-1"><em>Merci de modifier votre message en conséquence.</em></p>
                <small class="text-muted">Modéré par %s le %s</small>
            </div>',
            $post->getId(),
            $post->getAuthor()->getPseudo(),
            $quotedSection,
            htmlspecialchars($reason),
            $moderator->getPseudo(),
            (new \DateTime())->format('d/m/Y à H:i')
        );
        
        $editRequestPost->setContent($editRequestContent);
        
        $this->entityManager->persist($editRequestPost);
        
        // Ajouter une note interne si fournie
        if ($internalNote) {
            // TODO: Implémenter le système de notes internes de modération
        }
        
        $this->entityManager->flush();
    }

    private function handleHidePost(Post $post, string $reason, ?string $internalNote, $moderator, ?string $quotedText = null): void
    {
        // Sauvegarder le contenu original avant de le masquer
        if (!$post->isHidden()) {
            $post->setOriginalContent($post->getContent());
        }
        
        $quotedSection = '';
        if ($quotedText && !empty(trim($quotedText))) {
            $quotedSection = sprintf(
                '<div class="mt-2">
                    <strong>Extrait problématique :</strong>
                    <blockquote class="blockquote-sm border-start border-danger border-3 ps-3 mt-1">
                        <em>%s</em>
                    </blockquote>
                </div>',
                htmlspecialchars($quotedText)
            );
        }
        
        $hiddenContent = sprintf(
            '<div class="alert alert-danger moderation-notice">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-eye-slash me-2"></i>
                    <strong>Message masqué par la modération</strong>
                </div>
                <p class="mb-1"><strong>Message :</strong> 
                    <span class="text-muted">Message #%d de %s</span>
                </p>
                %s
                <p class="mb-1"><strong>Motif :</strong> %s</p>
                <small class="text-muted">Masqué par %s le %s</small>
            </div>',
            $post->getId(),
            $post->getAuthor()->getPseudo(),
            $quotedSection,
            htmlspecialchars($reason),
            $moderator->getPseudo(),
            (new \DateTime())->format('d/m/Y à H:i')
        );
        
        // Marquer le post comme masqué et sauvegarder les infos de modération
        $post->setContent($hiddenContent);
        $post->setIsHidden(true);
        $post->setModerationReason($reason);
        $post->setModerator($moderator);
        $post->setHiddenAt(new \DateTime());
        
        // Ajouter une note interne si fournie
        if ($internalNote) {
            // TODO: Implémenter le système de notes internes de modération
        }
        
        $this->entityManager->flush();
    }

    private function handleUnhidePost(Post $post, $moderator, ?string $reason = null): void
    {
        // Vérifier que le post est effectivement masqué
        if (!$post->isHidden()) {
            throw new \InvalidArgumentException('Ce message n\'est pas masqué.');
        }

        // Restaurer le contenu original
        if ($post->getOriginalContent()) {
            $post->setContent($post->getOriginalContent());
        }

        // Créer un message de démasquage dans le thread
        $unmaskPost = new Post();
        $unmaskPost->setThread($post->getThread());
        $unmaskPost->setAuthor($moderator);
        $unmaskPost->setType('moderation');
        
        $reasonSection = '';
        if ($reason && !empty(trim($reason))) {
            $reasonSection = sprintf('<p class="mb-1"><strong>Motif du démasquage :</strong> %s</p>', htmlspecialchars($reason));
        }

        $unmaskContent = sprintf(
            '<div class="alert alert-success moderation-notice">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-eye me-2"></i>
                    <strong>Message démasqué par la modération</strong>
                </div>
                <p class="mb-1"><strong>Message :</strong> 
                    <span class="text-primary">
                        <i class="fas fa-link me-1"></i>Message #%d
                    </span> de %s a été démasqué
                </p>
                <p class="mb-1"><strong>Motif original du masquage :</strong> %s</p>
                %s
                <small class="text-muted">Démasqué par %s le %s</small>
            </div>',
            $post->getId(),
            $post->getAuthor()->getPseudo(),
            htmlspecialchars($post->getModerationReason() ?: 'Non spécifié'),
            $reasonSection,
            $moderator->getPseudo(),
            (new \DateTime())->format('d/m/Y à H:i')
        );
        
        $unmaskPost->setContent($unmaskContent);
        
        // Réinitialiser les champs de modération
        $post->setIsHidden(false);
        $post->setModerationReason(null);
        $post->setModerator(null);
        $post->setHiddenAt(null);
        $post->setOriginalContent(null);
        
        $this->entityManager->persist($unmaskPost);
        $this->entityManager->flush();
    }
}