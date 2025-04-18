<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\Univers;
use App\Form\ThreadRoleplayType;
use App\Form\ThreadType;
use App\Form\PostRoleplayType;
use App\Form\PostType;
use App\Repository\CharacterRepository;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Repository\UniversRepository;
use App\Repository\ReadPostRepository;
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

    #[Route('/univers/{universeSlug}/choisir-forum', name: 'app_choose_forum_new_thread')]
    #[IsGranted('ROLE_USER')]
    public function chooseForum(string $universeSlug, UniversRepository $universRepository, ForumRepository $forumRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Récupérer les forums de cet univers, regroupés par catégorie
        $forumsRP = $forumRepository->findBy(['universe' => $univers, 'isRoleplay' => true], ['name' => 'ASC']);
        $forumsHRP = $forumRepository->findBy(['universe' => $univers, 'isRoleplay' => false], ['name' => 'ASC']);
        
        // Récupérer également les forums des elseworlds de cet univers
        $elseworlds = $univers->getElseworlds();
        $elseworldsForumsRP = [];
        $elseworldsForumsHRP = [];
        
        foreach ($elseworlds as $elseworld) {
            $elseworldsForumsRP[$elseworld->getId()] = [
                'elseworld' => $elseworld,
                'forums' => $forumRepository->findBy(['elseworld' => $elseworld, 'isRoleplay' => true], ['name' => 'ASC'])
            ];
            
            $elseworldsForumsHRP[$elseworld->getId()] = [
                'elseworld' => $elseworld,
                'forums' => $forumRepository->findBy(['elseworld' => $elseworld, 'isRoleplay' => false], ['name' => 'ASC'])
            ];
        }
        
        return $this->render('thread/choose_forum.html.twig', [
            'univers' => $univers,
            'forumsRP' => $forumsRP,
            'forumsHRP' => $forumsHRP,
            'elseworldsForumsRP' => $elseworldsForumsRP,
            'elseworldsForumsHRP' => $elseworldsForumsHRP,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_univers_index'),
                $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $universeSlug]),
                'Choisir un forum' => $this->generateUrl('app_choose_forum_new_thread', ['universeSlug' => $universeSlug]),
            ]),
        ]);
    }

    #[Route('/univers/{universeSlug}/forum/{id}/nouvelle-discussion', name: 'app_forum_new_thread')]
    #[IsGranted('ROLE_USER')]
    public function newThread(string $universeSlug, Request $request, Forum $forum, UniversRepository $universRepository, CharacterRepository $characterRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if ($forum->getStatus() === 'closed') {
            $this->addFlash('error', 'Ce forum est fermé aux nouvelles discussions.');
            return $this->redirectToRoute('app_univers_forums', ['slug' => $universeSlug]);
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

           
            
            // Utiliser le contenu du premier post spécifié séparément de la description du thread
            $post->setContent($isRpForum ? $thread->getFirstPostContent() : $thread->getDescription());

            if ($isRpForum) {
                $post->setType('roleplay');
            }
            
            if ($isRpForum && $thread->getCharacterCreator()) {
                $post->setCharacter($thread->getCharacterCreator());
                $thread->addParticipant($thread->getCharacterCreator());
            }

            if ($isRpForum && $thread->getNpcs()) {
                foreach ($thread->getNpcs() as $npc) {
                    $post->addNpc($npc);
                }
            }

            // Generate slug from the title
            $slug = $this->generateSlug($thread->getTitle());
            $thread->setSlug($slug);
            
            $thread->setAuthor($this->getUser());
            // La date est déjà initialisée dans le constructeur
            $this->entityManager->persist($thread);
            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre discussion a été créée avec succès.');

            return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
        }

        return $this->render('thread/new.html.twig', [
            'univers' => $univers,
            'form' => $form->createView(),
            'forum' => $forum,
            'isRpForum' => $isRpForum,
            'hasValidatedCharacters' => $hasValidatedCharacters,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_univers_index'),
                $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $universeSlug]),
                'Forums' => $this->generateUrl('app_univers_forums', ['slug' => $universeSlug]),
                $forum->getName() => $this->generateUrl('app_forum_show', ['universeSlug' => $universeSlug, 'id' => $forum->getId()]),
                'Nouvelle discussion' => $this->generateUrl('app_forum_new_thread', ['universeSlug' => $universeSlug, 'id' => $forum->getId()]),
            ]),
        ]);
    }

    #[Route('/univers/{universeSlug}/thread/{id}', name: 'app_thread_show')]
    public function show(
        string $universeSlug, 
        Thread $thread, 
        Request $request, 
        UniversRepository $universRepository, 
        CharacterRepository $characterRepository,
        ReadPostRepository $readPostRepository
    ): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Vérifier que le thread appartient bien à un forum de cet univers
        $forum = $thread->getForum();
        $forumUniverse = $forum->getUniverse();
        
        // Si le forum a un univers, vérifier qu'il s'agit bien de l'univers demandé
        if ($forumUniverse && $forumUniverse->getId() !== $univers->getId()) {
            throw $this->createNotFoundException('Cette discussion n\'appartient pas à cet univers');
        }
        
        // Si le forum appartient à un elseworld, vérifier que l'elseworld appartient à l'univers
        $elseworld = $forum->getElseworld();
        if ($elseworld && $elseworld->getParentUniverse()->getId() !== $univers->getId()) {
            throw $this->createNotFoundException('Cette discussion appartient à un elseworld qui n\'est pas lié à cet univers');
        }
        
        // Marquer les messages du thread comme lus pour l'utilisateur connecté
        if ($this->getUser()) {
            $readPostRepository->markThreadAsRead($this->getUser(), $thread);
        }
        
        // Gestion spéciale pour les fiches de personnage
        if ($thread->isCharacterSheet()) {
            return $this->showCharacterSheet($universeSlug, $thread, $request, $univers);
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
                    return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
                }

                $thread->addParticipant($post->getCharacter());
            }

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
        }

        return $this->render('thread/show.html.twig', [
            'univers' => $univers,
            'thread' => $thread,
            'form' => $form->createView(),
            'isRoleplay' => $isRpThread,
            'userCanPost' => $this->getUser() && ($thread->isOpen() ||
                $thread->getAuthor() === $this->getUser() ||
                ($isRpThread && isset($userCharacters) && count($userCharacters) > 0)),
            'breadcrumbs' => $this->getBreadcrumbsForThread($univers, $thread),
            'canReply' => $thread->isOpen() || $thread->getAuthor() === $this->getUser(),
            'posts' => $thread->getPosts(),
            'userHasCharacters' => $this->getUser() && $characterRepository->findValidatedCharactersForUser($this->getUser()),
        ]);
    }

    #[Route('/univers/{universeSlug}/roleplay/{id}', name: 'app_roleplay_thread_show')]
    public function showRolePlay(string $universeSlug, Thread $thread, Request $request, UniversRepository $universRepository, CharacterRepository $characterRepository): Response
    {
        // Rediriger vers la route standard
        return $this->redirectToRoute('app_thread_show', [
            'universeSlug' => $universeSlug,
            'id' => $thread->getId()
        ]);
    }


    /**
     * Affiche une fiche de personnage
     */
    private function showCharacterSheet(string $universeSlug, Thread $thread, Request $request, Univers $univers): Response
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

            return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
        }

        $isOwner = $this->getUser() && $character->getUser() === $this->getUser();
        $isModerator = $this->isGranted('ROLE_MODERATOR');

        // Générer le tableau de breadcrumbs avec le format correct
        $breadcrumbsArray = [
            ['name' => 'Accueil', 'url' => $this->generateUrl('app_univers_index')],
            ['name' => $univers->getName(), 'url' => $this->generateUrl('app_univers_show', ['slug' => $universeSlug])],
        ];

        // Ajouter le forum parent s'il existe
        if ($thread->getForum()) {
            $forum = $thread->getForum();
            $breadcrumbsArray[] = [
                'name' => 'Forums',
                'url' => $this->generateUrl('app_univers_forums', ['slug' => $universeSlug])
            ];
            
            // Si le forum appartient à un elseworld, l'ajouter dans le breadcrumb
            if ($forum->getElseworld()) {
                $elseworld = $forum->getElseworld();
                $breadcrumbsArray[] = [
                    'name' => 'Elseworlds',
                    'url' => $this->generateUrl('app_univers_elseworlds', ['slug' => $universeSlug])
                ];
                $breadcrumbsArray[] = [
                    'name' => $elseworld->getName(),
                    'url' => $this->generateUrl('app_elseworld_show', [
                        'universeSlug' => $universeSlug,
                        'elseworldSlug' => $elseworld->getSlug()
                    ])
                ];
            }
            
            $breadcrumbsArray[] = [
                'name' => $forum->getName(),
                'url' => $this->generateUrl('app_forum_show', [
                    'universeSlug' => $universeSlug, 
                    'id' => $forum->getId()
                ])
            ];
        }

        // Ajouter le titre du thread comme élément actif
        $breadcrumbsArray[] = $thread->getTitle();

        return $this->render('thread/character_sheet.html.twig', [
            'univers' => $univers,
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
    #[Route('/univers/{universeSlug}/thread/{id}/update-character-status', name: 'app_thread_update_character_status', methods: ['POST'])]
    public function updateCharacterStatus(string $universeSlug, Thread $thread, Request $request, UniversRepository $universRepository, CharacterRepository $characterRepository, ForumRepository $forumRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
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
        $targetForumName = match ($newStatus) {
            'validated' => 'Fiches validées',
            'rejected', 'abandoned' => 'Fiches refusées',
            'editing' => 'Fiches en attente',
            default => 'Fiches en attente',
        };

        $targetForum = $forumRepository->findOneBy(['name' => $targetForumName, 'universe' => $univers]);
        if ($targetForum) {
            $thread->setForum($targetForum);
        }

        // Ajouter un message dans le thread pour indiquer le changement de statut
        $statusPost = new Post();
        $statusPost->setThread($thread);
        $statusPost->setAuthor($this->getUser());

        $alertClass = match ($newStatus) {
            'validated' => 'success',
            'rejected' => 'danger',
            'abandoned' => 'secondary',
            default => 'warning',
        };

        $statusLabel = match ($newStatus) {
            'draft' => 'Brouillon',
            'pending' => 'En attente de validation',
            'validated' => 'Validé',
            'rejected' => 'Refusé',
            'abandoned' => 'Abandonné',
            'editing' => 'En cours d\'édition',
        };

        $statusContent = "<div class=\"alert alert-$alertClass\">" .
            "Statut mis à jour : <strong>$statusLabel</strong>" .
            ($moderationNote && $isModerator ? "<br>Note: $moderationNote" : "") .
            "</div>";

        $statusPost->setContent($statusContent);

        $this->entityManager->persist($statusPost);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le statut de la fiche a été mis à jour avec succès.');

        return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
    }

    #[Route('/univers/{universeSlug}/thread/{id}/delete', name: 'app_thread_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(string $universeSlug, Request $request, Thread $thread, UniversRepository $universRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Vérifier que l'utilisateur est autorisé à supprimer le thread
        if ($thread->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour supprimer cette discussion.');
        }

        // Vérifier le CSRF token
        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_thread_'.$thread->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Action non autorisée');
        }

        $this->entityManager->remove($thread);
        $this->entityManager->flush();

        $this->addFlash('success', 'La discussion a été supprimée avec succès.');

        return $this->redirectToRoute('app_univers_forums', ['slug' => $universeSlug]);
    }

    #[Route('/univers/{universeSlug}/thread/{id}/publish', name: 'app_thread_publish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publish(string $universeSlug, Thread $thread, UniversRepository $universRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Vérifier que l'utilisateur est autorisé à publier le thread
        if ($thread->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour publier cette discussion.');
        }
        
        // Vérifier que c'est bien un brouillon
        if (!$thread->isDraft()) {
            $this->addFlash('error', 'Cette discussion n\'est pas un brouillon.');
            return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
        }
        
        // Publier le thread
        $thread->setIsDraft(false);
        $thread->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'La discussion a été publiée avec succès.');
        
        return $this->redirectToRoute('app_thread_show', ['universeSlug' => $universeSlug, 'id' => $thread->getId()]);
    }

    #[Route('/univers/{universeSlug}/threads', name: 'app_roleplay_threads')]
    public function list(string $universeSlug, UniversRepository $universRepository, ThreadRepository $threadRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Récupérer les threads de type roleplay liés à cet univers
        $threads = $threadRepository->findRoleplayThreadsByUniverse($univers);

        return $this->render('thread/index.html.twig', [
            'univers' => $univers,
            'threads' => $threads,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_univers_index'),
                $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $universeSlug]),
                'Scènes RP' => $this->generateUrl('app_roleplay_threads', ['universeSlug' => $universeSlug]),
            ]),
        ]);
    }

    #[Route('/univers/{universeSlug}/threads/filter', name: 'app_roleplay_threads_filter')]
    public function filter(string $universeSlug, Request $request, UniversRepository $universRepository, ThreadRepository $threadRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        $type = $request->query->get('type', 'all');
        $status = $request->query->get('status', '');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $threads = [];
        $title = 'Scènes RP';

        switch ($type) {
            case 'participating':
                $title = 'Mes participations';
                if ($user) {
                    $threads = $threadRepository->findThreadsWithUserParticipationByUniverse($user->getId(), $univers->getId(), $status);
                }
                break;

            case 'created':
                $title = 'Mes scènes créées';
                if ($user) {
                    $criteria = ['author' => $user, 'type' => 'roleplay'];
                    $criteria['forum.universe'] = $univers;
                    if ($status) {
                        $criteria['status'] = $status;
                    }
                    $threads = $threadRepository->findThreadsByAuthorAndUniverse($user->getId(), $univers->getId(), $status);
                }
                break;

            case 'recent':
                $title = 'Scènes récentes';
                if ($status === 'open') {
                    $threads = $threadRepository->findActiveThreadsByUniverse($univers->getId(), 20);
                } else {
                    $threads = $threadRepository->findRecentThreadsByUniverse($univers->getId(), $status, 20);
                }
                break;

            default:
                if ($status === 'open') {
                    $threads = $threadRepository->findActiveThreadsByUniverse($univers->getId());
                } else {
                    $threads = $threadRepository->findThreadsByUniverse($univers->getId(), $status);
                }
        }

        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_univers_index'),
            $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $universeSlug]),
            'Scènes RP' => $this->generateUrl('app_roleplay_threads', ['universeSlug' => $universeSlug])
        ];

        if ($title !== 'Scènes RP') {
            $breadcrumbs[$title] = $this->generateUrl('app_roleplay_threads_filter', ['universeSlug' => $universeSlug, 'type' => $type]);
        }

        return $this->render('thread/filter.html.twig', [
            'univers' => $univers,
            'threads' => $threads,
            'type' => $type,
            'status' => $status,
            'title' => $title,
            'breadcrumbs' => $this->breadcrumbService->generate($breadcrumbs),
        ]);
    }

    #[Route('/univers/{universeSlug}/character-sheets', name: 'app_character_sheets')]
    public function characterSheets(string $universeSlug, UniversRepository $universRepository, ThreadRepository $threadRepository, ForumRepository $forumRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        $pendingSheetsForum = $forumRepository->findOneBy(['name' => 'Fiches en attente', 'universe' => $univers]);
        $validatedSheetsForum = $forumRepository->findOneBy(['name' => 'Fiches validées', 'universe' => $univers]);
        $rejectedSheetsForum = $forumRepository->findOneBy(['name' => 'Fiches refusées', 'universe' => $univers]);

        $pendingSheets = $pendingSheetsForum ? $threadRepository->findBy(['forum' => $pendingSheetsForum, 'type' => 'character_sheet'], ['updatedAt' => 'DESC']) : [];
        $validatedSheets = $validatedSheetsForum ? $threadRepository->findBy(['forum' => $validatedSheetsForum, 'type' => 'character_sheet'], ['updatedAt' => 'DESC']) : [];
        $rejectedSheets = $rejectedSheetsForum ? $threadRepository->findBy(['forum' => $rejectedSheetsForum, 'type' => 'character_sheet'], ['updatedAt' => 'DESC']) : [];

        return $this->render('thread/character_sheets.html.twig', [
            'univers' => $univers,
            'pendingSheets' => $pendingSheets,
            'validatedSheets' => $validatedSheets,
            'rejectedSheets' => $rejectedSheets,
            'canModerate' => $this->isGranted('ROLE_MODERATOR'),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_univers_index'),
                $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $universeSlug]),
                'Fiches de Personnages' => $this->generateUrl('app_character_sheets', ['universeSlug' => $universeSlug]),
            ]),
        ]);
    }

    #[Route('/univers/{universeSlug}/character-sheets/mine', name: 'app_character_sheets_mine')]
    #[IsGranted('ROLE_USER')]
    public function myCharacterSheets(string $universeSlug, UniversRepository $universRepository, ThreadRepository $threadRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        $myCharacterSheets = $threadRepository->findCharacterSheetsByUserAndUniverse($this->getUser(), $univers);

        return $this->render('thread/my_character_sheets.html.twig', [
            'univers' => $univers,
            'characterSheets' => $myCharacterSheets,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_univers_index'),
                $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $universeSlug]),
                'Fiches de Personnages' => $this->generateUrl('app_character_sheets', ['universeSlug' => $universeSlug]),
                'Mes Fiches' => $this->generateUrl('app_character_sheets_mine', ['universeSlug' => $universeSlug]),
            ]),
        ]);
    }

    #[Route('/univers/{universeSlug}/post/{id}/quote', name: 'app_post_quote')]
    #[IsGranted('ROLE_USER')]
    public function quote(string $universeSlug, Post $post, UniversRepository $universRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        $thread = $post->getThread();
        
        // Rediriger vers le formulaire de réponse avec le contenu cité
        return $this->redirectToRoute('app_thread_show', [
            'universeSlug' => $universeSlug,
            'id' => $thread->getId(),
            'quote' => $post->getId()
        ]);
    }

    #[Route('/univers/{universeSlug}/thread/{id}/edit', name: 'app_thread_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(string $universeSlug, Thread $thread, Request $request, UniversRepository $universRepository, CharacterRepository $characterRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Vérifier que l'utilisateur est l'auteur ou un modérateur
        if ($thread->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour éditer cette discussion');
        }
        
        $isRpThread = $thread->getType() === 'roleplay';
        
        if ($isRpThread) {
            $userCharacters = $characterRepository->findValidatedCharactersForUser($this->getUser());
            $form = $this->createForm(ThreadRoleplayType::class, $thread, [
                'characters' => $userCharacters,
            ]);
        } else {
            $form = $this->createForm(ThreadType::class, $thread);
        }
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Generate slug from the title
            $slug = $this->generateSlug($thread->getTitle());
            $thread->setSlug($slug);
            
            $thread->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            
            $this->addFlash('success', 'La discussion a été modifiée avec succès');
            
            return $this->redirectToRoute('app_thread_show', [
                'universeSlug' => $universeSlug,
                'id' => $thread->getId()
            ]);
        }
        
        return $this->render('thread/edit.html.twig', [
            'univers' => $univers,
            'form' => $form->createView(),
            'thread' => $thread,
            'isRpThread' => $isRpThread,
            'breadcrumbs' => $this->getBreadcrumbsForThread($univers, $thread),
        ]);
    }
    
    #[Route('/univers/{universeSlug}/thread/{id}/sticky', name: 'app_thread_sticky')]
    #[IsGranted('ROLE_MODERATOR')]
    public function sticky(string $universeSlug, Thread $thread, UniversRepository $universRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Épingler le thread
        $thread->setSticky(true);
        $thread->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        
        $this->addFlash('success', 'La discussion a été épinglée avec succès');
        
        return $this->redirectToRoute('app_thread_show', [
            'universeSlug' => $universeSlug,
            'id' => $thread->getId()
        ]);
    }
    
    #[Route('/univers/{universeSlug}/thread/{id}/unsticky', name: 'app_thread_unsticky')]
    #[IsGranted('ROLE_MODERATOR')]
    public function unsticky(string $universeSlug, Thread $thread, UniversRepository $universRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Désépingler le thread
        $thread->setSticky(false);
        $thread->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        
        $this->addFlash('success', 'La discussion a été désépinglée avec succès');
        
        return $this->redirectToRoute('app_thread_show', [
            'universeSlug' => $universeSlug,
            'id' => $thread->getId()
        ]);
    }
    
    #[Route('/univers/{universeSlug}/thread/{id}/close', name: 'app_thread_close')]
    #[IsGranted('ROLE_MODERATOR')]
    public function close(string $universeSlug, Thread $thread, UniversRepository $universRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Fermer le thread
        $thread->setStatus('closed');
        $thread->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        
        // Ajouter un message système dans le thread
        $systemPost = new Post();
        $systemPost->setThread($thread);
        $systemPost->setAuthor($this->getUser());
        $systemPost->setContent('<div class="alert alert-warning">Cette discussion a été verrouillée par un modérateur.</div>');
        
        $this->entityManager->persist($systemPost);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'La discussion a été verrouillée avec succès');
        
        return $this->redirectToRoute('app_thread_show', [
            'universeSlug' => $universeSlug,
            'id' => $thread->getId()
        ]);
    }
    
    #[Route('/univers/{universeSlug}/thread/{id}/open', name: 'app_thread_open')]
    #[IsGranted('ROLE_MODERATOR')]
    public function open(string $universeSlug, Thread $thread, UniversRepository $universRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Rouvrir le thread
        $thread->setStatus('open');
        $thread->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        
        // Ajouter un message système dans le thread
        $systemPost = new Post();
        $systemPost->setThread($thread);
        $systemPost->setAuthor($this->getUser());
        $systemPost->setContent('<div class="alert alert-success">Cette discussion a été rouverte par un modérateur.</div>');
        
        $this->entityManager->persist($systemPost);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'La discussion a été rouverte avec succès');
        
        return $this->redirectToRoute('app_thread_show', [
            'universeSlug' => $universeSlug,
            'id' => $thread->getId()
        ]);
    }

    private function getBreadcrumbsForForum(Univers $univers, Forum $forum, array $additional = []): array
    {
        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_univers_index'),
            $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $univers->getSlug()]),
        ];

        // Si le forum appartient à un elseworld, l'ajouter dans le breadcrumb
        if ($forum->getElseworld()) {
            $elseworld = $forum->getElseworld();
            $breadcrumbs['Elseworlds'] = $this->generateUrl('app_univers_elseworlds', ['slug' => $univers->getSlug()]);
            $breadcrumbs[$elseworld->getName()] = $this->generateUrl('app_elseworld_show', [
                'universeSlug' => $univers->getSlug(),
                'elseworldSlug' => $elseworld->getSlug()
            ]);
        } else {
            $breadcrumbs['Forums'] = $this->generateUrl('app_univers_forums', ['slug' => $univers->getSlug()]);
        }

        $currentForum = $forum;
        $parentForums = [];

        while ($parent = $currentForum->getParent()) {
            $parentForums[] = $parent;
            $currentForum = $parent;
        }

        $parentForums = array_reverse($parentForums);

        foreach ($parentForums as $parentForum) {
            $breadcrumbs[$parentForum->getName()] = $this->generateUrl('app_forum_show', [
                'universeSlug' => $univers->getSlug(),
                'id' => $parentForum->getId()
            ]);
        }

        $breadcrumbs[$forum->getName()] = $this->generateUrl('app_forum_show', [
            'universeSlug' => $univers->getSlug(),
            'id' => $forum->getId()
        ]);

        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }

        return $this->breadcrumbService->generate($breadcrumbs);
    }

    private function getBreadcrumbsForThread(Univers $univers, Thread $thread): array
    {
        $forum = $thread->getForum();
        return $this->getBreadcrumbsForForum($univers, $forum, [
            $thread->getTitle() => $this->generateUrl('app_thread_show', [
                'universeSlug' => $univers->getSlug(),
                'id' => $thread->getId()
            ])
        ]);
    }
    
    /**
     * Generate a URL-friendly slug from a string
     * 
     * @param string $text The text to slugify
     * @return string
     */
    private function generateSlug(string $text): string
    {
        // Remove accents
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9-] remove; Lower()', $text);
        
        // Replace spaces with hyphens
        $text = str_replace(' ', '-', $text);
        
        // Remove any remaining non-alphanumeric characters except for hyphens
        $text = preg_replace('/[^a-z0-9-]/', '', $text);
        
        // Remove multiple consecutive hyphens
        $text = preg_replace('/-+/', '-', $text);
        
        // Trim hyphens from beginning and end
        $text = trim($text, '-');
        
        // Ensure slug isn't empty
        if (empty($text)) {
            $text = 'discussion-' . time();
        }
        
        return $text;
    }
}
