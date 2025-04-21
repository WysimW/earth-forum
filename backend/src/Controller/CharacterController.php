<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Forum;
use App\Entity\Thread;
use App\Entity\Character;
use App\Entity\Npc;
use App\Entity\Univers;
use App\Form\CharacterType;
use App\Form\NpcType;
use App\Service\BreadcrumbService;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Repository\UniversRepository;
use App\Service\CharacterForumManager;
use App\Repository\CharacterRepository;
use App\Repository\NpcRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/characters')]
class CharacterController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BreadcrumbService $breadcrumbService;
    private ForumRepository $forumRepository;
    private CharacterForumManager $characterForumManager;
    private SluggerInterface $slugger;

    public function __construct(
        EntityManagerInterface $entityManager,
        BreadcrumbService $breadcrumbService,
        ForumRepository $forumRepository,
        CharacterForumManager $characterForumManager,
        SluggerInterface $slugger
    ) {
        $this->entityManager = $entityManager;
        $this->breadcrumbService = $breadcrumbService;
        $this->forumRepository = $forumRepository;
        $this->characterForumManager = $characterForumManager;
        $this->slugger = $slugger;
    }

    #[Route('/', name: 'app_roleplay_characters', methods: ['GET'])]
    #[Route('/', name: 'app_characters_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(CharacterRepository $characterRepository, NpcRepository $npcRepository, UniversRepository $universRepository): Response
    {
        $user = $this->getUser();
        $characters = $characterRepository->findBy(['user' => $user]);
        $npcs = $npcRepository->findBy(['user' => $user]);
        
        // Récupérer tous les univers
        $universes = $universRepository->findAll();
        
        // Structure pour regrouper PJ et PNJ par univers et elseworlds
        $contentByUniverse = [];
        $contentWithoutUniverse = [
            'characters' => [],
            'npcs' => []
        ];
        
        // Initialiser les tableaux pour chaque univers
        foreach ($universes as $universe) {
            $contentByUniverse[$universe->getId()] = [
                'universe' => $universe,
                'mainContent' => [
                    'characters' => [],
                    'npcs' => [],
                    'total' => 0
                ],
                'elseworlds' => [],
                'total' => 0
            ];
        }
        
        // Classer les personnages par univers/elseworld
        foreach ($characters as $character) {
            $universe = $character->getUniverse();
            $elseworld = $character->getElseworld();
            
            if ($universe) {
                $universeId = $universe->getId();
                
                if ($elseworld) {
                    $elseWorldId = $elseworld->getId();
                    
                    // Créer l'entrée pour l'elseworld s'il n'existe pas encore
                    if (!isset($contentByUniverse[$universeId]['elseworlds'][$elseWorldId])) {
                        $contentByUniverse[$universeId]['elseworlds'][$elseWorldId] = [
                            'elseworld' => $elseworld,
                            'characters' => [],
                            'npcs' => [],
                            'total' => 0
                        ];
                    }
                    
                    // Ajouter le personnage à l'elseworld
                    $contentByUniverse[$universeId]['elseworlds'][$elseWorldId]['characters'][] = $character;
                    $contentByUniverse[$universeId]['elseworlds'][$elseWorldId]['total']++;
                } else {
                    // Si pas d'elseworld, ajouter au contenu principal de l'univers
                    $contentByUniverse[$universeId]['mainContent']['characters'][] = $character;
                    $contentByUniverse[$universeId]['mainContent']['total']++;
                }
                
                // Compteur total de l'univers
                $contentByUniverse[$universeId]['total']++;
            } else {
                // Sans univers
                $contentWithoutUniverse['characters'][] = $character;
            }
        }
        
        // Classer les PNJ par univers/elseworld
        foreach ($npcs as $npc) {
            $universe = $npc->getUniverse();
            $elseworld = $npc->getElseworld();
            
            if ($universe) {
                $universeId = $universe->getId();
                
                if ($elseworld) {
                    $elseWorldId = $elseworld->getId();
                    
                    // Créer l'entrée pour l'elseworld s'il n'existe pas encore
                    if (!isset($contentByUniverse[$universeId]['elseworlds'][$elseWorldId])) {
                        $contentByUniverse[$universeId]['elseworlds'][$elseWorldId] = [
                            'elseworld' => $elseworld,
                            'characters' => [],
                            'npcs' => [],
                            'total' => 0
                        ];
                    }
                    
                    // Ajouter le PNJ à l'elseworld
                    $contentByUniverse[$universeId]['elseworlds'][$elseWorldId]['npcs'][] = $npc;
                    $contentByUniverse[$universeId]['elseworlds'][$elseWorldId]['total']++;
                } else {
                    // Si pas d'elseworld, ajouter au contenu principal de l'univers
                    $contentByUniverse[$universeId]['mainContent']['npcs'][] = $npc;
                    $contentByUniverse[$universeId]['mainContent']['total']++;
                }
                
                // Compteur total de l'univers
                $contentByUniverse[$universeId]['total']++;
            } else {
                // Sans univers
                $contentWithoutUniverse['npcs'][] = $npc;
            }
        }
        
        // Filtrer les univers qui n'ont pas de personnages ni de PNJ
        $contentByUniverse = array_filter($contentByUniverse, function($item) {
            return $item['total'] > 0;
        });
        
        return $this->render('characters/index.html.twig', [
            'contentByUniverse' => $contentByUniverse,
            'contentWithoutUniverse' => $contentWithoutUniverse,
            'totalWithoutUniverse' => count($contentWithoutUniverse['characters']) + count($contentWithoutUniverse['npcs']),
            'characters' => $characters, // Gardé pour rétrocompatibilité
            'npcs' => $npcs, // Gardé pour rétrocompatibilité
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes Personnages' => $this->generateUrl('app_roleplay_characters'),
            ]),
        ]);
    }

    #[Route('/new', name: 'app_roleplay_character_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, UniversRepository $universRepository): Response
    {
        $character = new Character();
        $character->setUser($this->getUser());
        
        $form = $this->createForm(CharacterType::class, $character);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Set status to "pending" by default when creating a new character
            $character->setStatus(Character::STATUS_DRAFT);
            $character->setStatusMessage('En cours de rédaction');
            
            $this->entityManager->persist($character);
            
            // Ensure character forums exist and get the pending forum for this universe
            $forums = $this->characterForumManager->ensureCharacterForumsExistForUniverse($character->getUniverse());
            $pendingForum = $forums['pending'];

            // Create character sheet thread
            $characterSheetThread = new Thread();
            $characterSheetThread->setTitle('Fiche de ' . $character->getName());
            $characterSheetThread->setAuthor($this->getUser());
            $characterSheetThread->setForum($pendingForum);
            $characterSheetThread->setType('character_sheet');
            $characterSheetThread->setStatus('open');
            $characterSheetThread->setCharacterSheet($character);
            $characterSheetThread->setSlug($this->slugger->slug('fiche-' . $character->getName())->lower());
            $characterSheetThread->setUniverse($character->getUniverse());
            
            // Create first post with character details
            $initialPost = new Post();
            $initialPost->setThread($characterSheetThread);
            $initialPost->setAuthor($this->getUser());
            $initialPost->setContent($this->renderView('characters/sheet_content.html.twig', [
                'character' => $character
            ]));
            
            $this->entityManager->persist($characterSheetThread);
            $this->entityManager->persist($initialPost);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre personnage a été créé avec succès et est en attente de validation.');
            
            return $this->redirectToRoute('app_roleplay_characters');
        }
        
        return $this->render('characters/new.html.twig', [
            'form' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes Personnages' => $this->generateUrl('app_roleplay_characters'),
                'Nouveau Personnage' => $this->generateUrl('app_roleplay_character_new'),
            ]),
        ]);
    }

    #[Route('/new/elseworld/{id}', name: 'app_character_new_elseworld', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newForElseworld(Request $request, \App\Entity\Elseworld $elseworld): Response
    {
        $character = new Character();
        $character->setUser($this->getUser());
        $character->setElseworld($elseworld);
        $character->setUniverse($elseworld->getParentUniverse());
        
        $form = $this->createForm(CharacterType::class, $character);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Set status to "pending" by default when creating a new character
            $character->setStatus(Character::STATUS_DRAFT);
            $character->setStatusMessage('En cours de rédaction');
            
            $this->entityManager->persist($character);
            
            // Ensure character forums exist and get the pending forum for this universe
            $forums = $this->characterForumManager->ensureCharacterForumsExistForUniverse($character->getUniverse());
            $pendingForum = $forums['pending'];

            // Create character sheet thread
            $characterSheetThread = new Thread();
            $characterSheetThread->setTitle('Fiche de ' . $character->getName() . ' (Elseworld: ' . $elseworld->getName() . ')');
            $characterSheetThread->setAuthor($this->getUser());
            $characterSheetThread->setForum($pendingForum);
            $characterSheetThread->setType('character_sheet');
            $characterSheetThread->setStatus('open');
            $characterSheetThread->setCharacterSheet($character);
            $characterSheetThread->setSlug($this->slugger->slug('fiche-' . $character->getName())->lower());
            if (method_exists($characterSheetThread, 'setElseworld')) {
                $characterSheetThread->setElseworld($elseworld);
            }
            $characterSheetThread->setUniverse($character->getUniverse());
            
            // Create first post with character details
            $initialPost = new Post();
            $initialPost->setThread($characterSheetThread);
            $initialPost->setAuthor($this->getUser());
            $initialPost->setContent($this->renderView('characters/sheet_content.html.twig', [
                'character' => $character
            ]));
            
            $this->entityManager->persist($characterSheetThread);
            $this->entityManager->persist($initialPost);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre personnage a été créé avec succès et est en attente de validation.');
            
            return $this->redirectToRoute('app_roleplay_characters');
        }
        
        return $this->render('characters/new.html.twig', [
            'form' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Elseworld: ' . $elseworld->getName() => $this->generateUrl('app_elseworld_show', ['id' => $elseworld->getId()]),
                'Nouveau Personnage' => $this->generateUrl('app_character_new_elseworld', ['id' => $elseworld->getId()]),
            ]),
            'elseworld' => $elseworld
        ]);
    }

    #[Route('/{id}/edit', name: 'app_roleplay_character_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Character $character): Response
    {
        if ($character->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas éditer ce personnage.');
        }
        
        $oldStatus = $character->getStatus();
        
        $form = $this->createForm(CharacterType::class, $character);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // If character was validated before, set to editing status
            if ($oldStatus === Character::STATUS_VALIDATED) {
                $character->setStatus(Character::STATUS_EDITING);
                $character->setStatusMessage('Modifications en cours');
            }
            
            // Update character sheet thread if it exists
            $characterSheetThread = $character->getMainCharacterSheetThread();
            if ($characterSheetThread) {
                // Move to appropriate forum based on status
                $targetForum = $this->getForumByCharacterStatus($character->getStatus(), $character->getUniverse());
                if ($targetForum) {
                    $characterSheetThread->setForum($targetForum);
                }
                
                // Update first post with new character details
                if (!$characterSheetThread->getPosts()->isEmpty()) {
                    $initialPost = $characterSheetThread->getPosts()->first();
                    if ($initialPost) {
                        $initialPost->setContent($this->renderView('characters/sheet_content.html.twig', ['character' => $character]));
                    }
                }
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre personnage a été mis à jour avec succès.');
            
            return $this->redirectToRoute('app_roleplay_characters');
        }
        
        return $this->render('characters/edit.html.twig', [
            'form' => $form->createView(),
            'character' => $character,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes Personnages' => $this->generateUrl('app_roleplay_characters'),
                'Éditer ' . $character->getName() => $this->generateUrl('app_roleplay_character_edit', ['id' => $character->getId()]),
            ]),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_roleplay_character_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Character $character): Response
    {
        if ($character->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce personnage.');
        }

        if ($this->isCsrfTokenValid('delete' . $character->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($character);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_roleplay_characters');
    }

    #[Route('/{id}/update-status', name: 'app_roleplay_character_update_status', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateStatus(Request $request, Character $character): Response
    {
        if ($character->getUser() !== $this->getUser() && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier le statut de ce personnage.');
        }
        
        $newStatus = $request->request->get('status');
        $statusMessage = $request->request->get('status_message');
        $moderationNote = $request->request->get('moderation_note');
        
        $oldStatus = $character->getStatus();
        $character->setStatus($newStatus);
        
        if ($statusMessage) {
            $character->setStatusMessage($statusMessage);
        }
        
        if ($moderationNote && $this->isGranted('ROLE_MODERATOR')) {
            $character->setModerationNote($moderationNote);
        }
        
        // If character is being validated
        if ($newStatus === Character::STATUS_VALIDATED && $oldStatus !== Character::STATUS_VALIDATED) {
            $character->setValidatedAt(new \DateTimeImmutable());
        }
        
        // Update thread forum
        $characterSheetThread = $character->getMainCharacterSheetThread();
        if ($characterSheetThread) {
            $targetForum = $this->getForumByCharacterStatus($newStatus, $character->getUniverse());
            if ($targetForum) {
                $characterSheetThread->setForum($targetForum);
            }
        }
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Le statut du personnage a été mis à jour avec succès.');
        
        return $this->redirectToRoute('app_roleplay_character_show', ['id' => $character->getId()]);
    }

    #[Route('/{id}/validate', name: 'app_roleplay_character_validate', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function validate(Request $request, Character $character): Response
    {   
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $moderationNote = $request->request->get('moderation_note', '');
        
        $character->setStatus(Character::STATUS_VALIDATED);
        $character->setStatusMessage('Personnage validé');
        $character->setValidatedAt(new \DateTimeImmutable());
        
        if ($moderationNote) {
            $character->setModerationNote($moderationNote);
        }
        
        // Update thread forum
        $characterSheetThread = $character->getMainCharacterSheetThread();
        if ($characterSheetThread) {
            $validatedCharactersForum = $this->forumRepository->findOneBy(['name' => 'Fiches validées']);
            if ($validatedCharactersForum) {
                $characterSheetThread->setForum($validatedCharactersForum);
                
                // Add a moderation post
                $moderationPost = new Post();
                $moderationPost->setThread($characterSheetThread);
                $moderationPost->setAuthor($this->getUser());
                $moderationPost->setContent('<div class="alert alert-success">Personnage validé par ' . $user->getPseudo() . 
                                           ($moderationNote ? '<br>Note: ' . $moderationNote : '') . '</div>');
                
                $this->entityManager->persist($moderationPost);
            }
        }
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Le personnage a été validé avec succès.');
        
        return $this->redirectToRoute('app_roleplay_character_show', ['id' => $character->getId()]);
    }

    #[Route('/{id}/reject', name: 'app_roleplay_character_reject', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function reject(Request $request, Character $character): Response
    {   
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $moderationNote = $request->request->get('moderation_note', '');
        
        $character->setStatus(Character::STATUS_REJECTED);
        $character->setStatusMessage('Personnage refusé');
        
        if ($moderationNote) {
            $character->setModerationNote($moderationNote);
        }
        
        // Update thread forum
        $characterSheetThread = $character->getMainCharacterSheetThread();
        if ($characterSheetThread) {
            $rejectedCharactersForum = $this->forumRepository->findOneBy(['name' => 'Fiches refusées']);
            if ($rejectedCharactersForum) {
                $characterSheetThread->setForum($rejectedCharactersForum);
                
                // Add a moderation post
                $moderationPost = new Post();
                $moderationPost->setThread($characterSheetThread);
                $moderationPost->setAuthor($this->getUser());
                $moderationPost->setContent('<div class="alert alert-danger">Personnage refusé par ' . $user->getPseudo() . 
                                           ($moderationNote ? '<br>Raison: ' . $moderationNote : '') . '</div>');
                
                $this->entityManager->persist($moderationPost);
            }
        }
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Le personnage a été refusé.');
        
        return $this->redirectToRoute('app_roleplay_character_show', ['id' => $character->getId()]);
    }

    private function getForumByCharacterStatus(string $status, ?Univers $universe = null): ?Forum
    {
        $forumName = match($status) {
            Character::STATUS_DRAFT, Character::STATUS_EDITING, Character::STATUS_PENDING => 'Fiches en attente',
            Character::STATUS_VALIDATED => 'Fiches validées',
            Character::STATUS_REJECTED, Character::STATUS_ABANDONED => 'Fiches refusées',
            default => null,
        };
        
        if (!$forumName) {
            return null;
        }
        
        // Search for a forum with the given name and universe
        $criteria = ['name' => $forumName];
        if ($universe) {
            $criteria['universe'] = $universe;
        }
        
        return $this->forumRepository->findOneBy($criteria);
    }

    #[Route('/{id}', name: 'app_roleplay_character_show', methods: ['GET'])]
    #[Route('/{id}', name: 'app_character_show', methods: ['GET'])]
    public function show(Character $character, ThreadRepository $threadRepository): Response
    {
        $threads = $threadRepository->findByParticipantId($character->getId());
        $characterSheetThread = $character->getMainCharacterSheetThread();
        
        return $this->render('characters/show.html.twig', [
            'character' => $character,
            'threads' => $threads,
            'characterSheetThread' => $characterSheetThread,
            'isOwner' => $this->getUser() && $character->getUser() === $this->getUser(),
            'isModerator' => $this->isGranted('ROLE_MODERATOR'),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Personnages' => $this->generateUrl('app_roleplay_characters'),
                $character->getName() => $this->generateUrl('app_roleplay_character_show', ['id' => $character->getId()]),
            ]),
        ]);
    }

    #[Route('/npc/new', name: 'app_npc_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newNpc(Request $request): Response
    {
        $npc = new Npc();
        $npc->setUser($this->getUser());
        $npc->setStatus('draft');
        
        $form = $this->createForm(NpcType::class, $npc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($npc);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre PNJ a été créé avec succès.');

            return $this->redirectToRoute('app_roleplay_characters');
        }

        return $this->render('characters/npc/new.html.twig', [
            'form' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes Personnages' => $this->generateUrl('app_roleplay_characters'),
                'Nouveau PNJ' => $this->generateUrl('app_npc_new'),
            ]),
        ]);
    }

    #[Route('/npc/{id}', name: 'app_npc_show', methods: ['GET'])]
    public function showNpc(Npc $npc): Response
    {
        return $this->render('characters/npc/show.html.twig', [
            'npc' => $npc,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes Personnages' => $this->generateUrl('app_roleplay_characters'),
                $npc->getName() => $this->generateUrl('app_npc_show', ['id' => $npc->getId()]),
            ]),
        ]);
    }

    #[Route('/npc/{id}/edit', name: 'app_npc_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function editNpc(Request $request, Npc $npc): Response
    {
        if ($npc->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce PNJ.');
        }

        $form = $this->createForm(NpcType::class, $npc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre PNJ a été modifié avec succès.');

            return $this->redirectToRoute('app_roleplay_characters');
        }

        return $this->render('characters/npc/edit.html.twig', [
            'form' => $form->createView(),
            'npc' => $npc,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_roleplay'),
                'Mes Personnages' => $this->generateUrl('app_roleplay_characters'),
                $npc->getName() => $this->generateUrl('app_npc_show', ['id' => $npc->getId()]),
                'Modifier' => $this->generateUrl('app_npc_edit', ['id' => $npc->getId()]),
            ]),
        ]);
    }

    #[Route('/npc/{id}', name: 'app_npc_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteNpc(Request $request, Npc $npc): Response
    {
        if ($npc->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce PNJ.');
        }

        if ($this->isCsrfTokenValid('delete'.$npc->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($npc);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre PNJ a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_roleplay_characters');
    }
}