<?php

namespace App\Controller\Admin;

use App\Entity\Forum;
use App\Entity\Univers;
use App\Form\ForumType;
use App\Entity\Elseworld;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/forums', name: 'admin_forum_')]
class AdminForumController extends AbstractController
{
    public function __construct(
        private ForumRepository $forumRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ForumRepository $forumRepository, EntityManagerInterface $entityManager): Response
    {
        $forums = $forumRepository->findAll();
        $universes = $entityManager->getRepository(Univers::class)->findAll();
        
        // Debug des univers
        $universesInfo = [];
        foreach ($universes as $universe) {
            $universesInfo[] = [
                'id' => $universe->getId(),
                'name' => $universe->getName()
            ];
        }
        
        // Générer la même URL que celle utilisée dans le template
        $bulkActionUrl =  $this->generateUrl('admin_forum_bulk_change_universe');

        return $this->render('admin/forum/index.html.twig', [
            'forums' => $forums,
            'universes' => $universes,
            'universesInfo' => $universesInfo, // Pour le débogage
            'bulkActionUrl' => $bulkActionUrl // Pour le débogage
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

    #[Route('/new/elseworld/{id}', name: 'new_elseworld', methods: ['GET', 'POST'])]
    public function newForElseworld(Request $request, EntityManagerInterface $entityManager, \App\Entity\Elseworld $elseworld): Response
    {
        $forum = new Forum();
        $forum->setElseworld($elseworld);
        
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification des relations
            if ($forum->getCategory() !== null && $forum->getParent() !== null) {
                $this->addFlash('error', 'Un forum ne peut pas être à la fois dans une catégorie et être un sous-forum.');
                return $this->render('admin/forum/new.html.twig', [
                    'forum' => $forum,
                    'form' => $form->createView(),
                    'elseworld' => $elseworld
                ]);
            }

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $forum->getName()), '-'));
            $forum->setSlug($slug);
            
            $entityManager->persist($forum);
            $entityManager->flush();

            $this->addFlash('success', 'Le forum a été créé avec succès dans l\'Elseworld ' . $elseworld->getName());
            return $this->redirectToRoute('app_elseworld_show', ['id' => $elseworld->getId()]);
        }

        return $this->render('admin/forum/new.html.twig', [
            'forum' => $forum,
            'form' => $form->createView(),
            'elseworld' => $elseworld,
            'breadcrumbs' => [
                'Accueil' => $this->generateUrl('app_home'),
                'Administration' => $this->generateUrl('admin_index'),
                'Forums' => $this->generateUrl('admin_forum_index'),
                'Elseworld: ' . $elseworld->getName() => $this->generateUrl('app_elseworld_show', ['id' => $elseworld->getId()]),
                'Nouveau Forum' => $this->generateUrl('admin_forum_new_elseworld', ['id' => $elseworld->getId()]),
            ]
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Request $request, ForumRepository $forumRepository): Response
    {
        $id = $request->attributes->get('id');
        $forum = $forumRepository->find($id);
        
        if (!$forum) {
            $this->addFlash('error', 'Forum non trouvé.');
            return $this->redirectToRoute('admin_forum_index');
        }
        
        return $this->render('admin/forum/show.html.twig', [
            'forum' => $forum
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ForumRepository $forumRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->attributes->get('id');
        $forum = $forumRepository->find($id);
        
        if (!$forum) {
            $this->addFlash('error', 'Forum non trouvé.');
            return $this->redirectToRoute('admin_forum_index');
        }
        
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
    public function delete(Request $request, ForumRepository $forumRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->attributes->get('id');
        $forum = $forumRepository->find($id);
        
        if (!$forum) {
            $this->addFlash('error', 'Forum non trouvé.');
            return $this->redirectToRoute('admin_forum_index');
        }
        
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

    #[Route('/admin/forums/bulk-change-universe', name: 'bulk_change_universe', methods: ['POST'])]
    public function bulkChangeUniverse(Request $request): Response
    {
        // 1. Récupérer les données du formulaire
        $forumIds = $request->request->all('forums');
        $universeId = $request->request->get('universe_id');
        $elseworldId = $request->request->get('elseworld_id');
        
        // 2. Valider les données
        if (empty($forumIds)) {
            $this->addFlash('warning', 'Veuillez sélectionner au moins un forum.');
            return $this->redirectToRoute('admin_forum_index');
        }
        
        if (empty($universeId)) {
            $this->addFlash('warning', 'Veuillez sélectionner un univers ou "Aucun univers".');
            return $this->redirectToRoute('admin_forum_index');
        }
        
        // 3. Récupérer les entités
        $universe = null;
        if ($universeId !== 'null') {
            $universe = $this->entityManager->getRepository(Univers::class)->find($universeId);
            if (!$universe) {
                $this->addFlash('danger', 'L\'univers sélectionné n\'existe pas.');
                return $this->redirectToRoute('admin_forum_index');
            }
        }
        
        $elseworld = null;
        if (!empty($elseworldId)) {
            $elseworld = $this->entityManager->getRepository(Elseworld::class)->find($elseworldId);
            if (!$elseworld) {
                $this->addFlash('danger', 'L\'elseworld sélectionné n\'existe pas.');
                return $this->redirectToRoute('admin_forum_index');
            }
            
            // Vérifier la cohérence entre univers et elseworld
            if ($universe && $elseworld->getParentUniverse()->getId() !== $universe->getId()) {
                $this->addFlash('danger', 'L\'elseworld sélectionné n\'appartient pas à l\'univers choisi.');
                return $this->redirectToRoute('admin_forum_index');
            }
        }
        
        // 4. Mettre à jour les forums
        $count = 0;
        $updatedForums = [];
        
        foreach ($forumIds as $id) {
            $forum = $this->forumRepository->find($id);
            if (!$forum) {
                continue;
            }
            
            // Enregistrer l'état avant modification pour les logs
            $oldUniverse = $forum->getUniverse() ? $forum->getUniverse()->getName() : 'aucun';
            $oldElseworld = $forum->getElseworld() ? $forum->getElseworld()->getName() : 'aucun';
            
            // Mettre à jour les relations
            $forum->setUniverse($universe);
            $forum->setElseworld($elseworld);
            
            $count++;
            $updatedForums[] = [
                'id' => $forum->getId(),
                'name' => $forum->getName(),
                'old_universe' => $oldUniverse,
                'new_universe' => $universe ? $universe->getName() : 'aucun',
            ];
        }
        
        // 5. Sauvegarder les modifications
        if ($count > 0) {
            $this->entityManager->flush();
            
            $universeName = $universe ? $universe->getName() : 'Aucun univers';
            $elseworldInfo = $elseworld ? " et l'elseworld {$elseworld->getName()}" : '';
            
            $this->addFlash('success', "{$count} forum(s) ont été mis à jour avec l'univers \"{$universeName}\"{$elseworldInfo}.");
        } else {
            $this->addFlash('warning', 'Aucun forum n\'a été mis à jour.');
        }
        
        return $this->redirectToRoute('admin_forum_index');
    }
}