<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\Univers;
use App\Service\LastPostService;
use App\Repository\PostRepository;
use App\Service\BreadcrumbService;
use App\Repository\ForumRepository;
use App\Repository\ThreadRepository;
use App\Repository\UniversRepository;
use App\Service\ForumStatisticsService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ForumCategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ForumController extends AbstractController
{
    private BreadcrumbService $breadcrumbService;
    private LastPostService $lastPostService;
    private ForumStatisticsService $forumStatsService;
    private $slugger;

    public function __construct(
        BreadcrumbService $breadcrumbService, 
        LastPostService $lastPostService,
        ForumStatisticsService $forumStatsService,
        SluggerInterface $slugger
    ) {
        $this->breadcrumbService = $breadcrumbService;
        $this->lastPostService = $lastPostService;
        $this->forumStatsService = $forumStatsService;
        $this->slugger = $slugger;
    }

    #[Route('/univers/{universeSlug}/forum/{id}', name: 'app_forum_show')]
    public function show(string $universeSlug, Forum $forum, Request $request, UniversRepository $universRepository, ForumRepository $forumRepository, ThreadRepository $threadRepository, PostRepository $postRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Vérifier que le forum appartient bien à cet univers
        $forumUniverse = $forum->getUniverse();
        if ($forumUniverse && $forumUniverse->getId() !== $univers->getId()) {
            throw $this->createNotFoundException('Ce forum n\'appartient pas à cet univers');
        }

        // Si c'est un forum d'elseworld, vérifier que l'elseworld appartient à l'univers
        $elseworld = $forum->getElseworld();
        if ($elseworld && $elseworld->getParentUniverse()->getId() !== $univers->getId()) {
            throw $this->createNotFoundException('Ce forum appartient à un elseworld qui n\'est pas lié à cet univers');
        }
        
        // Récupérer les sous-forums triés par type (important > roleplay > hrp) puis par position
        $subForums = $forumRepository->createQueryBuilder('f')
            ->where('f.parent = :parent')
            ->setParameter('parent', $forum)
            ->orderBy('CASE f.type 
                WHEN \'important\' THEN 1 
                WHEN \'roleplay\' THEN 2 
                WHEN \'hrp\' THEN 3 
                ELSE 4 END', 'ASC')
            ->addOrderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Enrichir les sous-forums avec les informations sur le dernier post et les statistiques
        foreach ($subForums as $subforum) {
            $lastPostInfo = $this->lastPostService->getLastPostInfoForForum($subforum->getId());
            $subforum->lastPostInfo = $lastPostInfo;
            
            // Ajouter les statistiques cumulées
            $stats = $this->forumStatsService->getForumStats($subforum->getId());
            $subforum->stats = $stats;
        }
        
        // Pagination des threads
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Récupérer les threads avec pagination
        $totalThreads = $threadRepository->count(['forum' => $forum]);
        $totalPages = ceil($totalThreads / $limit);
        $threads = $threadRepository->findBy(
            ['forum' => $forum], 
            ['sticky' => 'DESC', 'updatedAt' => 'DESC'],
            $limit,
            $offset
        );
        
        // Enrichir les threads avec les derniers posts
        foreach ($threads as $thread) {
            $lastPost = $postRepository->findLastPostForThread($thread->getId());
            $thread->lastPost = $lastPost;
            
            // Si c'est un thread RP, récupérer les participants
            if ($thread->getType() === 'roleplay') {
                $participants = $postRepository->findDistinctCharactersByThread($thread->getId());
                // Use public attribute for temporary data rather than accessing private property
                $thread->characterParticipants = $participants;
            }
        }
        
        // Récupération des informations du dernier post pour le forum courant
        $forum->lastPostInfo = $this->lastPostService->getLastPostInfoForForum($forum->getId());
        
        // Ajouter les statistiques cumulées du forum courant
        $forum->stats = $this->forumStatsService->getForumStats($forum->getId());
        
        return $this->render('forum/show.html.twig', [
            'univers' => $univers,
            'forum' => $forum,
            'subForums' => $subForums,
            'threads' => $threads,
            'breadcrumbs' => $this->getBreadcrumbsForForum($univers, $forum),
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalThreads
            ],
        ]);
    }

    private function getBreadcrumbsForForum(Univers $univers, Forum $forum, array $additional = []): array
    {
        $breadcrumbs = [
            'Accueil' => $this->generateUrl('app_univers_index'),
            $univers->getName() => $this->generateUrl('app_univers_show', ['slug' => $univers->getSlug()])
        ];
        
        $currentForum = $forum;
        $parentForums = [];
        
        while ($parent = $currentForum->getParent()) {
            $parentForums[] = $parent;
            $currentForum = $parent;
        }
        
        $parentForums = array_reverse($parentForums);
        
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

    #[Route('/by-universe-ajax', name: 'app_forums_by_universe_ajax', methods: ['GET'])]
    public function getForumsByUniverseAjax(Request $request, ForumRepository $forumRepository): JsonResponse
    {
        $universeId = $request->query->get('universe');
        
        if (!$universeId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Identifiant d\'univers manquant.'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Récupérer uniquement les forums de type roleplay pour cet univers
        $forums = $forumRepository->findBy(
            ['universe' => $universeId, 'type' => 'roleplay'], 
            ['name' => 'ASC']
        );
        
        $forumData = [];
        foreach ($forums as $forum) {
            $forumData[] = [
                'id' => $forum->getId(),
                'name' => $forum->getName(),
                'parent' => $forum->getParent() ? $forum->getParent()->getId() : null
            ];
        }
        
        return new JsonResponse([
            'success' => true,
            'forums' => $forumData
        ]);
    }

    #[Route('/create-ajax', name: 'app_forum_create_ajax', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createAjax(Request $request, EntityManagerInterface $entityManager, UniversRepository $universRepository, ForumRepository $forumRepository, ForumCategoryRepository $forumCategoryRepository): JsonResponse
    {
        // Vérifier le jeton CSRF
        if (!$this->isCsrfTokenValid('forum_create', $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token CSRF invalide.'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Récupérer les données JSON
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name']) || !isset($data['universe'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Données incomplètes.'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Récupérer l'univers
        $universe = $universRepository->find($data['universe']);
        
        if (!$universe) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Univers non trouvé.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Essayer de trouver la catégorie Terre
        $category = $forumCategoryRepository->findOneBy(['name' => 'Terre']);
        
        // Vérifier le type de forum
        $isRoleplayForum = isset($data['type']) && $data['type'] === 'roleplay';
        $isImportantForum = isset($data['type']) && $data['type'] === 'important';
        
        // Si ce n'est pas un forum de type roleplay ou si c'est un forum important, vérifier que l'utilisateur est admin
        if ((!$isRoleplayForum || $isImportantForum) && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Seuls les administrateurs peuvent créer des forums non-RP ou de type important.'
            ], Response::HTTP_FORBIDDEN);
        }
        
        // Si c'est un forum de type roleplay, vérifier les permissions spéciales
        if ($isRoleplayForum && !$isImportantForum) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            
            // Vérifier si l'utilisateur a l'autorisation de créer un forum RP
            if (!$user->canCreateRpForum() && !$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous n\'avez pas l\'autorisation de créer un forum de roleplay. Veuillez contacter un administrateur.'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Créer le nouveau forum
        $forum = new Forum();
        $forum->setName($data['name']);
        $forum->setUniverse($universe);
        
        // Générer le slug à partir du nom
        $slug = strtolower($this->slugger->slug($data['name']));
        $forum->setSlug($slug);
        
        // Ajouter le forum parent si spécifié
        if (isset($data['parent']) && $data['parent']) {
            $parentForum = $forumRepository->find($data['parent']);
            if ($parentForum) {
                $forum->setParent($parentForum);
            }
        }
        
        // Ajouter la description si présente
        if (isset($data['description']) && !empty($data['description'])) {
            $forum->setDescription($data['description']);
        }
        
        // Ajouter le type si présent
        if (isset($data['type']) && !empty($data['type'])) {
            $forum->setType($data['type']);
            // Si c'est un forum RP, définir isRoleplay à true
            if ($data['type'] === 'roleplay') {
                $forum->setIsRoleplay(true);
            }
        } else {
            $forum->setType('normal');
        }
        
        if ($category) {
            $forum->setCategory($category);
        }
        
        // Position par défaut
        $forum->setPosition(99);
        
        // Persister le forum
        $entityManager->persist($forum);
        
        // Si c'est un forum de roleplay, révoquer l'autorisation après création
        if ($isRoleplayForum && !$this->isGranted('ROLE_ADMIN')) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $user->setCanCreateRpForum(false);
            $entityManager->persist($user);
        }
        
        $entityManager->flush();
        
        // Retourner la réponse avec les données du forum créé
        return new JsonResponse([
            'success' => true,
            'forum' => [
                'id' => $forum->getId(),
                'name' => $forum->getName()
            ]
        ]);
    }
}