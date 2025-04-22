<?php

namespace App\Controller;

use App\Entity\Faction;
use App\Entity\Forum;
use App\Entity\Thread;
use App\Entity\Post;
use App\Entity\Univers;
use App\Form\FactionType;
use App\Repository\FactionRepository;
use App\Repository\ForumRepository;
use App\Repository\UniversRepository;
use App\Repository\CharacterRepository;
use App\Repository\ThreadRepository;
use App\Service\BreadcrumbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/factions')]
class FactionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BreadcrumbService $breadcrumbService;
    private SluggerInterface $slugger;

    public function __construct(
        EntityManagerInterface $entityManager,
        BreadcrumbService $breadcrumbService,
        SluggerInterface $slugger
    ) {
        $this->entityManager = $entityManager;
        $this->breadcrumbService = $breadcrumbService;
        $this->slugger = $slugger;
    }

    #[Route('/', name: 'app_factions_index', methods: ['GET'])]
    public function index(FactionRepository $factionRepository, UniversRepository $universRepository): Response
    {
        $universes = $universRepository->findAll();
        $factions = $factionRepository->findAll();
        
        return $this->render('faction/index.html.twig', [
            'universes' => $universes,
            'factions' => $factions,
            'breadcrumbs' => $this->getBreadcrumbs()
        ]);
    }

    #[Route('/univers/{universeSlug}', name: 'app_factions_by_universe', methods: ['GET'])]
    public function byUniverse(string $universeSlug, UniversRepository $universRepository, FactionRepository $factionRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        $factions = $factionRepository->findByUniverse($univers->getId());
        
        return $this->render('faction/by_universe.html.twig', [
            'univers' => $univers,
            'factions' => $factions,
            'breadcrumbs' => $this->getBreadcrumbsUniverse($univers)
        ]);
    }

    #[Route('/new', name: 'app_faction_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, UniversRepository $universRepository, ForumRepository $forumRepository): Response
    {   
        /** @var \App\Entity\User components/_breadcrumb.html.twig */
        $user = $this->getUser();
        
        if (!$user->canCreateFaction()) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de créer une faction. Veuillez contacter un administrateur.');
            return $this->redirectToRoute('app_factions_index');
        }
        
        $faction = new Faction();
        $faction->setFounder($user);
        
        // Formulaire adapté pour le frontoffice
        $form = $this->createForm(FactionType::class, $faction, [
            'front_office' => true
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Créer le forum 'Faction' s'il n'existe pas pour cet univers
            $univers = $faction->getUniverse();
            $factionForum = $this->ensureFactionForumExists($univers, $forumRepository);
            
            // Créer un thread pour la fiche de faction
            $thread = $this->createFactionThread($faction, $factionForum);
            
            $this->entityManager->persist($faction);
            $this->entityManager->persist($thread);
            
            // Révoquer l'autorisation de créer une faction
            $user->setCanCreateFaction(false);
            
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre faction a été créée avec succès !');
            return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('faction/new.html.twig', [
            'faction' => $faction,
            'form' => $form,
            'universes' => $universRepository->findAll(),
            'breadcrumbs' => $this->getBreadcrumbs(['Créer une faction' => ''])
        ]);
    }

    #[Route('/univers/{universeSlug}/faction/{factionSlug}', name: 'app_faction_show')]
    public function show(string $universeSlug, string $factionSlug, UniversRepository $universRepository, FactionRepository $factionRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        $faction = $factionRepository->findOneBy(['slug' => $factionSlug, 'universe' => $univers]);
        
        if (!$faction) {
            throw $this->createNotFoundException('La faction demandée n\'existe pas');
        }
        
        // Récupérer les threads associés à cette faction
        $threads = $faction->getScenes();
        
        return $this->render('faction/show.html.twig', [
            'univers' => $univers,
            'faction' => $faction,
            'threads' => $threads,
            'breadcrumbs' => $this->breadcrumbService->generate([
                'Accueil' => $this->generateUrl('app_univers_index'),
                $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $universeSlug]),
                'Factions' => $this->generateUrl('app_factions_by_universe', ['universeSlug' => $universeSlug]),
                $faction->getName() => $this->generateUrl('app_faction_show', ['universeSlug' => $universeSlug, 'factionSlug' => $factionSlug]),
            ]),
        ]);
    }

    #[Route('/univers/{universeSlug}/faction/{factionSlug}/debug', name: 'app_faction_debug')]
    public function debug(string $universeSlug, string $factionSlug, UniversRepository $universRepository, FactionRepository $factionRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        $faction = $factionRepository->findOneBy(['slug' => $factionSlug, 'universe' => $univers]);
        
        if (!$faction) {
            throw $this->createNotFoundException('La faction demandée n\'existe pas');
        }
        
        // Vérification et correction des threads liés à la faction
        $scenes = [];
        foreach ($faction->getScenes() as $scene) {
            $scenes[] = [
                'id' => $scene->getId(),
                'title' => $scene->getTitle(),
                'contains_faction' => $scene->getFactions()->contains($faction)
            ];
        }
        
        return $this->render('faction/debug.html.twig', [
            'univers' => $univers,
            'faction' => $faction,
            'scenes' => $scenes,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_faction_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Faction $faction): Response
    {
        /** @var \App\Entity\User components/_breadcrumb.html.twig */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est le fondateur de la faction ou un administrateur
        if ($faction->getFounder() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de modifier cette faction.');
            return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
        }
        
        $form = $this->createForm(FactionType::class, $faction, [
            'front_office' => true
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            
            // Mettre à jour le thread de la fiche de faction si nécessaire
            $this->updateFactionThread($faction);

            $this->addFlash('success', 'La faction a été mise à jour avec succès.');
            return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('faction/edit.html.twig', [
            'faction' => $faction,
            'form' => $form,
            'breadcrumbs' => $this->getBreadcrumbsFaction($faction, ['Modifier' => ''])
        ]);
    }

    #[Route('/{id}/delete', name: 'app_faction_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Faction $faction): Response
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est le fondateur de la faction ou un administrateur
        if ($faction->getFounder() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de supprimer cette faction.');
            return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
        }
        
        if ($this->isCsrfTokenValid('delete'.$faction->getId(), $request->request->get('_token'))) {
            // Récupérer l'univers pour la redirection après suppression
            $universe = $faction->getUniverse();
            
            // Trouver et supprimer le thread associé à la faction s'il existe
            $threads = $this->entityManager->getRepository(Thread::class)->findBy([
                'title' => 'Faction: ' . $faction->getName()
            ]);
            
            foreach ($threads as $thread) {
                $this->entityManager->remove($thread);
            }
            
            $this->entityManager->remove($faction);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'La faction a été supprimée avec succès.');
            
            return $this->redirectToRoute('app_factions_by_universe', [
                'universeSlug' => $universe->getSlug()
            ]);
        }

        return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
    }

    #[Route('/{id}/join', name: 'app_faction_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(Request $request, Faction $faction, CharacterRepository $characterRepository): Response
    {
        if ($this->isCsrfTokenValid('join'.$faction->getId(), $request->request->get('_token'))) {
            $characterId = $request->request->get('character_id');
            
            if (!$characterId) {
                $this->addFlash('error', 'Vous devez sélectionner un personnage.');
                return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
            }
            
            $character = $characterRepository->find($characterId);
            
            if (!$character) {
                $this->addFlash('error', 'Le personnage sélectionné n\'existe pas.');
                return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
            }
            
            // Vérifier que le personnage appartient bien à l'utilisateur
            if ($character->getUser() !== $this->getUser()) {
                $this->addFlash('error', 'Ce personnage ne vous appartient pas.');
                return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
            }
            
            // Vérifier que le personnage n'est pas déjà dans la faction
            if ($faction->getCharacters()->contains($character)) {
                $this->addFlash('error', 'Ce personnage est déjà membre de la faction.');
                return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
            }
            
            // Vérifier que la faction est ouverte aux inscriptions
            if ($faction->getStatus() !== Faction::STATUS_OPEN) {
                $this->addFlash('error', 'Cette faction n\'est pas ouverte aux inscriptions.');
                return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
            }
            
            // Ajouter le personnage à la faction
            $faction->addCharacter($character);
            $this->entityManager->flush();
            
            $this->addFlash('success', $character->getName() . ' a rejoint la faction ' . $faction->getName() . ' avec succès.');
        }
        
        return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
    }

    #[Route('/{id}/leave', name: 'app_faction_leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(Request $request, Faction $faction, CharacterRepository $characterRepository): Response
    {
        if ($this->isCsrfTokenValid('leave'.$faction->getId(), $request->request->get('_token'))) {
            $characterId = $request->request->get('character_id');
            
            if (!$characterId) {
                $this->addFlash('error', 'Vous devez sélectionner un personnage.');
                return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
            }
            
            $character = $characterRepository->find($characterId);
            
            if (!$character) {
                $this->addFlash('error', 'Le personnage sélectionné n\'existe pas.');
                return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
            }
            
            // Vérifier que le personnage appartient bien à l'utilisateur
            if ($character->getUser() !== $this->getUser()) {
                $this->addFlash('error', 'Ce personnage ne vous appartient pas.');
                return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
            }
            
            // Vérifier que le personnage est bien dans la faction
            if (!$faction->getCharacters()->contains($character)) {
                $this->addFlash('error', 'Ce personnage n\'est pas membre de la faction.');
                return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
            }
            
            // Retirer le personnage de la faction
            $faction->removeCharacter($character);
            $this->entityManager->flush();
            
            $this->addFlash('success', $character->getName() . ' a quitté la faction ' . $faction->getName() . ' avec succès.');
        }
        
        return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
    }

    #[Route('/{id}/toggle-status', name: 'app_faction_toggle_status', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleStatus(Request $request, Faction $faction): Response
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est le fondateur de la faction ou un administrateur
        if ($faction->getFounder() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de modifier cette faction.');
            return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
        }
        
        if ($this->isCsrfTokenValid('toggle_status'.$faction->getId(), $request->request->get('_token'))) {
            // Inverser le statut de la faction
            $newStatus = $faction->getStatus() === Faction::STATUS_OPEN ? Faction::STATUS_CLOSED : Faction::STATUS_OPEN;
            $faction->setStatus($newStatus);
            
            $this->entityManager->flush();
            
            $statusMessage = $newStatus === Faction::STATUS_OPEN ? 'ouverte' : 'fermée';
            $this->addFlash('success', 'La faction est maintenant ' . $statusMessage . ' aux inscriptions.');
        }
        
        return $this->redirectToRoute('app_faction_show', ['universeSlug' => $faction->getUniverse()->getSlug(), 'factionSlug' => $faction->getSlug()]);
    }

    #[Route('/my-factions', name: 'app_my_factions_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myFactions(FactionRepository $factionRepository, UniversRepository $universRepository, CharacterRepository $characterRepository): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        // Récupérer tous les personnages de l'utilisateur
        $userCharacters = $characterRepository->findBy(['user' => $user]);
        
        // Récupérer toutes les factions auxquelles appartiennent ces personnages
        $userFactions = [];
        foreach ($userCharacters as $character) {
            foreach ($character->getFactionsRelation() as $faction) {
                $userFactions[$faction->getId()] = $faction;
            }
        }
        
        // Récupérer également les factions dont l'utilisateur est le fondateur
        $ownedFactions = $factionRepository->findBy(['founder' => $user]);
        foreach ($ownedFactions as $faction) {
            $userFactions[$faction->getId()] = $faction;
        }
        
        // Récupérer tous les univers
        $universes = $universRepository->findAll();
        
        return $this->render('faction/my_factions.html.twig', [
            'universes' => $universes,
            'factions' => array_values($userFactions),
            'breadcrumbs' => $this->getBreadcrumbs(['Mes factions' => ''])
        ]);
    }

    /**
     * S'assure qu'un forum 'Factions' existe pour l'univers
     */
    private function ensureFactionForumExists($univers, ForumRepository $forumRepository): Forum
    {
        // Chercher un forum "Factions" qui serait lié à l'univers
        $factionsForum = $forumRepository->findOneBy([
            'name' => 'Factions',
            'universe' => $univers
        ]);
        
        // Si le forum n'existe pas, le créer
        if (!$factionsForum) {
            $factionsForum = new Forum();
            $factionsForum->setName('Factions');
            $factionsForum->setDescription('Forum dédié aux factions de l\'univers ' . $univers->getName());
            $factionsForum->setType('important');
            $factionsForum->setUniverse($univers);
            $factionsForum->setPosition(1); // Position élevée pour le mettre en haut
            
            $this->entityManager->persist($factionsForum);
            $this->entityManager->flush();
        }
        
        return $factionsForum;
    }

    /**
     * Crée un thread pour la fiche de faction
     */
    private function createFactionThread(Faction $faction, Forum $forum): Thread
    {
        $thread = new Thread();
        $thread->setTitle('Faction: ' . $faction->getName());
        $thread->setForum($forum);
        $thread->setAuthor($faction->getFounder());
        $thread->setType('important');
        $thread->setStatus('open');
        $thread->setDescription('Fiche de la faction ' . $faction->getName());
        
        // Contenu du premier message
        $firstPostContent = $this->renderView('faction/partials/_faction_sheet.html.twig', [
            'faction' => $faction
        ]);
        
        $thread->setFirstPostContent($firstPostContent);
        
        // Créer le premier post
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($faction->getFounder());
        $post->setContent($firstPostContent);
        
        $this->entityManager->persist($thread);
        $this->entityManager->persist($post);
        
        return $thread;
    }

    /**
     * Met à jour le thread de la fiche de faction
     */
    private function updateFactionThread(Faction $faction): void
    {
        // Chercher le thread de la faction
        $threads = $this->entityManager->getRepository(Thread::class)->findBy([
            'title' => 'Faction: ' . $faction->getName()
        ]);
        
        if (count($threads) > 0) {
            $thread = $threads[0];
            
            // Mettre à jour le premier post du thread
            $firstPostContent = $this->renderView('faction/partials/_faction_sheet.html.twig', [
                'faction' => $faction
            ]);
            
            // Récupérer le premier post du thread
            $firstPost = $this->entityManager->getRepository(Post::class)
                ->findOneBy(['thread' => $thread], ['createdAt' => 'ASC']);
            
            if ($firstPost) {
                $firstPost->setContent($firstPostContent);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Génère les breadcrumbs de base
     */
    private function getBreadcrumbs(array $additional = []): array
    {
        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_univers_index'),
            'Factions' => $this->generateUrl('app_factions_index')
        ];
        
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->breadcrumbService->generate($breadcrumbs);
    }

    /**
     * Génère les breadcrumbs pour un univers
     */
    private function getBreadcrumbsUniverse($univers, array $additional = []): array
    {
        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_univers_index'),
            $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $univers->getSlug()]),
            'Factions' => $this->generateUrl('app_factions_by_universe', ['universeSlug' => $univers->getSlug()])
        ];
        
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->breadcrumbService->generate($breadcrumbs);
    }

    /**
     * Génère les breadcrumbs pour une faction
     */
    private function getBreadcrumbsFaction(Faction $faction, array $additional = []): array
    {
        $univers = $faction->getUniverse();
        
        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_univers_index'),
            $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $univers->getSlug()]),
            'Factions' => $this->generateUrl('app_factions_by_universe', ['universeSlug' => $univers->getSlug()]),
            $faction->getName() => $this->generateUrl('app_faction_show', ['universeSlug' => $univers->getSlug(), 'factionSlug' => $faction->getSlug()])
        ];
        
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->breadcrumbService->generate($breadcrumbs);
    }
} 