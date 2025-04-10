<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\Post;
use App\Entity\Thread;
use App\Form\ThreadRoleplayType;
use App\Form\ThreadType;
use App\Form\PostRoleplayType;
use App\Form\PostType;
use App\Repository\CharacterRepository;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Service\BreadcrumbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ThreadController extends AbstractController
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

    #[Route('/forum/{id}/nouvelle-discussion', name: 'app_forum_new_thread')]
    #[IsGranted('ROLE_USER')]
    public function newThread(Request $request, Forum $forum, CharacterRepository $characterRepository): Response
    {   
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if ($forum->getStatus() === 'closed') {
            $this->addFlash('error', 'Ce forum est fermé aux nouvelles discussions.');
            return $this->redirectToRoute('app_roleplay');
        }

        $thread = new Thread();
        $thread->setForum($forum);
        $thread->setAuthor($this->getUser());
        $thread->setStatus('open');
        
        $isRpForum = $forum->isRoleplay();
        $thread->setType($isRpForum ? 'roleplay' : 'discussion');
        $hasValidatedCharacters = false;
        if ($isRpForum) {
            $userCharacters = $characterRepository->findValidatedCharactersForUser($this->getUser());
            if (count($userCharacters) !== 0) {
                $hasValidatedCharacters = true;
            }
            $form = $this->createForm(ThreadRoleplayType::class, $thread, [
                'characters' => $userCharacters,
            ]);
        } else {
            $form = $this->createForm(ThreadType::class, $thread);
        }
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $post = new Post();
            $post->setThread($thread);
            $post->setAuthor($this->getUser());
            $post->setContent($thread->getDescription());
            
            if ($isRpForum && $thread->getCharacterCreator()) {
                $post->setCharacter($thread->getCharacterCreator());
                $thread->addParticipant($thread->getCharacterCreator());
            }
            
            $thread->setAuthor($this->getUser());
            // La date est déjà initialisée dans le constructeur
            $this->entityManager->persist($thread);
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre discussion a été créée avec succès.');
            
            return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
        }
        
        return $this->render('thread/new.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
            'isRoleplay' => $isRpForum,
            'breadcrumbs' => $this->getBreadcrumbsForForum($forum, ['Nouvelle discussion' => null]),
            'user' => $user,
            'hasValidatedCharacters' => $hasValidatedCharacters,
        ]);
    }
    
    #[Route('/thread/{id}', name: 'app_thread_show')]
    public function show(Thread $thread, Request $request, CharacterRepository $characterRepository): Response
    {
        // Gestion spéciale pour les fiches de personnage
        if ($thread->isCharacterSheet()) {
            return $this->showCharacterSheet($thread, $request);
        }
        
        $isRpThread = $thread->getType() === 'roleplay';
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());
        
        if ($isRpThread && $this->getUser()) {
            if ($thread->isOpen()) {
                $userCharacters = $characterRepository->findValidatedCharactersForUser($this->getUser());
            } else {
                $userCharacters = $characterRepository->findValidatedParticipantsForUser($this->getUser(), $thread);
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
        
        return $this->render('thread/show.html.twig', [
            'thread' => $thread,
            'form' => $form->createView(),
            'isRoleplay' => $isRpThread,
            'userCanPost' => $this->getUser() && ($thread->isOpen() || 
                                               $thread->getAuthor() === $this->getUser() ||
                                               ($isRpThread && isset($userCharacters) && count($userCharacters) > 0)),
            'breadcrumbs' => $this->getBreadcrumbsForThread($thread),
            'canReply' => $thread->isOpen() || $thread->getAuthor() === $this->getUser(),
            'posts' => $thread->getPosts(),
            'userHasCharacters' => $this->getUser() && $characterRepository->findValidatedCharactersForUser($this->getUser()),
        ]);
    }

    /**
     * Affiche une fiche de personnage
     */
    private function showCharacterSheet(Thread $thread, Request $request): Response
    {
        $character = $thread->getCharacterSheet();
        if (!$character) {
            throw $this->createNotFoundException('Cette fiche de personnage n\'existe pas ou a été supprimée.');
        }
        
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());
        
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            
            return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
        }
        
        $isOwner = $this->getUser() && $character->getUser() === $this->getUser();
        $isModerator = $this->isGranted('ROLE_MODERATOR');

        // Générer le tableau de breadcrumbs avec le format correct
        $breadcrumbsArray = [
            ['name' => 'Accueil', 'url' => $this->generateUrl('app_roleplay')],
        ];

        // Ajouter le forum parent s'il existe
        if ($thread->getForum()) {
            $breadcrumbsArray[] = [
                'name' => $thread->getForum()->getName(),
                'url' => $this->generateUrl('app_forum_show', ['id' => $thread->getForum()->getId()])
            ];
        }

        // Ajouter le titre du thread comme élément actif
        $breadcrumbsArray[] = $thread->getTitle();
        
        return $this->render('thread/character_sheet.html.twig', [
            'thread' => $thread,
            'character' => $character,
            'posts' => $thread->getPosts(),
            'form' => $form->createView(),
            'isOwner' => $isOwner,
            'isModerator' => $isModerator,
            'canReply' => $thread->isOpen() || $isOwner || $isModerator,
            'breadcrumbs' => $breadcrumbsArray,
        ]);
    }

    /**
     * Met à jour le statut d'une fiche de personnage
     */
    #[Route('/thread/{id}/update-character-status', name: 'app_thread_update_character_status', methods: ['POST'])]
    public function updateCharacterStatus(Thread $thread, Request $request, CharacterRepository $characterRepository, ForumRepository $forumRepository): Response
    {
        if (!$thread->isCharacterSheet()) {
            throw $this->createNotFoundException('Cette discussion n\'est pas une fiche de personnage.');
        }
        
        $character = $thread->getCharacterSheet();
        if (!$character) {
            throw $this->createNotFoundException('Le personnage associé à cette fiche n\'existe pas ou a été supprimé.');
        }
        
        $isOwner = $this->getUser() && $character->getUser() === $this->getUser();
        $isModerator = $this->isGranted('ROLE_MODERATOR');
        
        if (!$isOwner && !$isModerator) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier le statut de cette fiche.');
        }
        
        $newStatus = $request->request->get('status');
        $statusMessage = $request->request->get('status_message');
        $moderationNote = $request->request->get('moderation_note');
        
        // Mettre à jour le personnage
        $character->setStatus($newStatus);
        
        if ($statusMessage) {
            $character->setStatusMessage($statusMessage);
        }
        
        if ($moderationNote && $isModerator) {
            $character->setModerationNote($moderationNote);
        }
        
        // Si le statut est "validated" et que ce n'était pas déjà le cas
        if ($newStatus === 'validated' && !$character->isValidated()) {
            $character->setValidatedAt(new \DateTimeImmutable());
        }
        
        // Déplacer le thread vers le forum approprié
        $targetForumName = match($newStatus) {
            'validated' => 'Fiches validées',
            'rejected', 'abandoned' => 'Fiches refusées',
            default => 'Fiches en attente',
        };
        
        $targetForum = $forumRepository->findOneBy(['name' => $targetForumName]);
        if ($targetForum) {
            $thread->setForum($targetForum);
        }
        
        // Ajouter un message dans le thread pour indiquer le changement de statut
        $statusPost = new Post();
        $statusPost->setThread($thread);
        $statusPost->setAuthor($this->getUser());
        
        $alertClass = match($newStatus) {
            'validated' => 'success',
            'rejected' => 'danger',
            'abandoned' => 'secondary',
            default => 'warning',
        };
        
        $statusLabel = match($newStatus) {
            'draft' => 'Brouillon',
            'pending' => 'En attente de validation',
            'validated' => 'Validé',
            'rejected' => 'Refusé',
            'abandoned' => 'Abandonné',
            'editing' => 'En cours d\'édition',
        };
        
        $statusContent = "<div class=\"alert alert-$alertClass\">".
            "Statut mis à jour : <strong>$statusLabel</strong>".
            ($moderationNote && $isModerator ? "<br>Note: $moderationNote" : "").
            "</div>";
        
        $statusPost->setContent($statusContent);
        
        $this->entityManager->persist($statusPost);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Le statut de la fiche a été mis à jour avec succès.');
        
        return $this->redirectToRoute('app_thread_show', ['id' => $thread->getId()]);
    }
    
    #[Route('/thread/{id}/delete', name: 'app_thread_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Thread $thread): Response
    {
        if ($thread->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce thread.');
        }

        if ($this->isCsrfTokenValid('delete_thread_' . $thread->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($thread);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le thread a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_roleplay');
    }

    #[Route('/threads', name: 'app_roleplay_threads')]
    public function list(ThreadRepository $threadRepository): Response
    {
        $threads = $threadRepository->findBy(
            ['type' => 'roleplay'],
            ['updatedAt' => 'DESC']
        );
        
        return $this->render('threads/index.html.twig', [
            'threads' => $threads,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Scènes RP' => $this->generateUrl('app_roleplay_threads'),
            ]),
        ]);
    }
    
    #[Route('/threads/filter', name: 'app_roleplay_threads_filter')]
    public function filter(Request $request, ThreadRepository $threadRepository): Response
    {
        $type = $request->query->get('type', 'all');
        $status = $request->query->get('status', '');
    
        $user = $this->getUser();
        $threads = [];
        $title = 'Scènes RP';
        
        switch ($type) {
            case 'participating':
                $title = 'Mes participations';
                if ($user) {
                    $threads = $threadRepository->findThreadsWithUserParticipation($user->getId(), $status);
                }
                break;
                
            case 'created':
                $title = 'Mes scènes créées';
                if ($user) {
                    $criteria = ['author' => $user, 'type' => 'roleplay'];
                    if ($status) {
                        $criteria['status'] = $status;
                    }
                    $threads = $threadRepository->findBy($criteria, ['updatedAt' => 'DESC']);
                }
                break;
                
            case 'recent':
                $title = 'Scènes récentes';
                if ($status === 'open') {
                    $threads = $threadRepository->findActiveThreads(20);
                } else {
                    $criteria = ['type' => 'roleplay'];
                    if ($status) {
                        $criteria['status'] = $status;
                    }
                    $threads = $threadRepository->findBy($criteria, ['updatedAt' => 'DESC'], 20);
                }
                break;
                
            default:
                if ($status === 'open') {
                    $threads = $threadRepository->findActiveThreads();
                } else {
                    $criteria = ['type' => 'roleplay'];
                    if ($status) {
                        $criteria['status'] = $status;
                    }
                    $threads = $threadRepository->findBy($criteria, ['updatedAt' => 'DESC']);
                }
        }
        
        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_roleplay'),
            'Scènes RP' => $this->generateUrl('app_roleplay_threads')
        ];
        
        if ($title !== 'Scènes RP') {
            $breadcrumbs[$title] = $this->generateUrl('app_roleplay_threads_filter', ['type' => $type]);
        }
        
        return $this->render('threads/filter.html.twig', [
            'threads' => $threads,
            'type' => $type,
            'status' => $status,
            'title' => $title,
            'breadcrumbs' => $this->breadcrumbService->generate($breadcrumbs),
        ]);
    }
    
    #[Route('/character-sheets', name: 'app_character_sheets')]
    public function characterSheets(ThreadRepository $threadRepository, ForumRepository $forumRepository): Response
    {
        $pendingSheetsForum = $forumRepository->findOneBy(['name' => 'Fiches en attente']);
        $validatedSheetsForum = $forumRepository->findOneBy(['name' => 'Fiches validées']);
        $rejectedSheetsForum = $forumRepository->findOneBy(['name' => 'Fiches refusées']);
        
        $pendingSheets = $pendingSheetsForum ? $threadRepository->findBy(['forum' => $pendingSheetsForum, 'type' => 'character_sheet'], ['updatedAt' => 'DESC']) : [];
        $validatedSheets = $validatedSheetsForum ? $threadRepository->findBy(['forum' => $validatedSheetsForum, 'type' => 'character_sheet'], ['updatedAt' => 'DESC']) : [];
        $rejectedSheets = $rejectedSheetsForum ? $threadRepository->findBy(['forum' => $rejectedSheetsForum, 'type' => 'character_sheet'], ['updatedAt' => 'DESC']) : [];
        
        return $this->render('thread/character_sheets.html.twig', [
            'pendingSheets' => $pendingSheets,
            'validatedSheets' => $validatedSheets,
            'rejectedSheets' => $rejectedSheets,
            'canModerate' => $this->isGranted('ROLE_MODERATOR'),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Fiches de Personnages' => $this->generateUrl('app_character_sheets'),
            ]),
        ]);
    }

    #[Route('/character-sheets/mine', name: 'app_character_sheets_mine')]
    #[IsGranted('ROLE_USER')]
    public function myCharacterSheets(ThreadRepository $threadRepository): Response
    {
        $myCharacterSheets = $threadRepository->findCharacterSheetsByUser($this->getUser());
        
        return $this->render('thread/my_character_sheets.html.twig', [
            'characterSheets' => $myCharacterSheets,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Fiches de Personnages' => $this->generateUrl('app_character_sheets'),
                'Mes Fiches' => $this->generateUrl('app_character_sheets_mine'),
            ]),
        ]);
    }
    
    private function getBreadcrumbsForForum(Forum $forum, array $additional = []): array
    {
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
        
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->breadcrumbService->generate($breadcrumbs);
    }
    
    private function getBreadcrumbsForThread(Thread $thread): array
    {
        $forum = $thread->getForum();
        $breadcrumbs = $this->getBreadcrumbsForForum($forum);
        $breadcrumbs[$thread->getTitle()] = $this->generateUrl('app_thread_show', ['id' => $thread->getId()]);
        return $breadcrumbs;
    }
}