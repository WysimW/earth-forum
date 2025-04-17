<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use App\Repository\ReadPostRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\UniversRepository;
use App\Entity\Univers;

#[Route('/unread-messages')]
#[IsGranted('ROLE_USER')]
class UnreadMessagesController extends AbstractController
{
    private ReadPostRepository $readPostRepository;
    private PostRepository $postRepository;
    private UniversRepository $universRepository;
    private UserRepository $userRepository;

    public function __construct(
        ReadPostRepository $readPostRepository,
        PostRepository $postRepository,
        UniversRepository $universRepository,
        UserRepository $userRepository
    ) {
        $this->readPostRepository = $readPostRepository;
        $this->postRepository = $postRepository;
        $this->universRepository = $universRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/', name: 'app_unread_messages_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Récupérer un univers par défaut
        $defaultUniverse = $this->universRepository->findOneBy([], ['id' => 'ASC']);
        $universeSlug = $defaultUniverse ? $defaultUniverse->getSlug() : 'dc';
        
        // Récupérer les messages non lus (3 catégories)
        $standardUnreadPosts = $this->readPostRepository->findUnreadPostsForUser($user);
        $participatingUnreadPosts = $this->readPostRepository->findUnreadPostsInParticipatingThreads($user);
        $adminUnreadPosts = $this->readPostRepository->findUnreadAdminPostsForUser($user);
        
        return $this->render('unread_messages/index.html.twig', [
            'standardUnreadPosts' => $standardUnreadPosts,
            'participatingUnreadPosts' => $participatingUnreadPosts,
            'adminUnreadPosts' => $adminUnreadPosts,
            'universeSlug' => $universeSlug,
            'breadcrumbs' => [
                ['name' => 'Accueil', 'url' => $this->generateUrl('app_front_office')],
                ['name' => 'Nouveaux Messages', 'url' => $this->generateUrl('app_unread_messages_index')]
            ]
        ]);
    }
    
    #[Route('/mark-read/{id}', name: 'app_unread_messages_mark_read')]
    public function markAsRead(Post $post): Response
    {
        $user = $this->getUser();
        $this->readPostRepository->markAsRead($user, $post);
        
        // Rediriger vers la page des messages non lus
        return $this->redirectToRoute('app_unread_messages_index');
    }
    
    #[Route('/mark-thread-read/{id}', name: 'app_unread_messages_mark_thread_read')]
    public function markThreadAsRead(Thread $thread): Response
    {
        $user = $this->getUser();
        $this->readPostRepository->markThreadAsRead($user, $thread);
        
        // Rediriger vers la page des messages non lus
        return $this->redirectToRoute('app_unread_messages_index');
    }
    
    #[Route('/mark-all-read', name: 'app_unread_messages_mark_all_read')]
    public function markAllAsRead(): Response
    {
        $user = $this->getUser();
        $unreadPosts = $this->readPostRepository->findUnreadPostsForUser($user);
        
        foreach ($unreadPosts as $post) {
            $this->readPostRepository->markAsRead($user, $post);
        }
        
        return $this->redirectToRoute('app_unread_messages_index');
    }
    
    #[Route('/count', name: 'app_unread_messages_count', methods: ['GET'])]
    public function getUnreadCount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer la préférence de filtrage de l'utilisateur
        $filterNonParticipating = $user->getFilterNonParticipatingMessages() ?? false;
        
        // Récupérer tous les messages non lus selon les paramètres
        $standardUnreadPosts = $this->readPostRepository->findUnreadPostsForUser($user);
        $participatingUnreadPosts = $this->readPostRepository->findUnreadPostsInParticipatingThreads($user);
        $adminUnreadPosts = $this->readPostRepository->findUnreadAdminPostsForUser($user);
        
        // Calculer le nombre total selon la préférence
        $totalCount = count($adminUnreadPosts) + count($participatingUnreadPosts);
        
        if (!$filterNonParticipating) {
            $totalCount += count($standardUnreadPosts);
        }
        
        // Retourner les données au format JSON
        return new JsonResponse([
            'total' => $totalCount,
            'admin' => count($adminUnreadPosts),
            'participating' => count($participatingUnreadPosts),
            'standard' => count($standardUnreadPosts)
        ]);
    }
    
    #[Route('/update-preferences', name: 'app_unread_messages_update_preferences', methods: ['POST'])]
    public function updatePreferences(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $filterNonParticipating = $request->request->getBoolean('filterNonParticipatingMessages');
        
        // Mettre à jour la préférence de l'utilisateur
        $user->setFilterNonParticipatingMessages($filterNonParticipating);
        
        // Sauvegarder les modifications
        $entityManager->persist($user);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }
    
    #[Route('/check-forum/{id}', name: 'app_unread_messages_check_forum', methods: ['GET'])]
    public function checkForum(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['hasUnread' => false]);
        }
        
        // Récupérer la préférence de filtrage de l'utilisateur
        $filterNonParticipating = $user->getFilterNonParticipatingMessages() ?? false;
        
        // Récupérer les IDs de tous les sous-forums de ce forum (récursif)
        $forumIds = $this->getAllSubForumIds($id, $entityManager);
        $forumIds[] = $id; // Ajouter le forum actuel
        
        // Requête pour vérifier si le forum ou ses sous-forums contiennent des messages non lus
        $qb = $entityManager->createQueryBuilder();
        $qb->select('COUNT(p.id)')
           ->from('App\Entity\Post', 'p')
           ->join('p.thread', 't')
           ->join('t.forum', 'f')
           ->leftJoin('App\Entity\ReadPost', 'rp', 'WITH', 'rp.post = p.id AND rp.user = :user')
           ->where('f.id IN (:forumIds)')
           ->andWhere('rp.id IS NULL')
           ->andWhere('p.isDraft = false')
           ->andWhere('p.author != :user') // Exclure les messages de l'utilisateur lui-même
           ->setParameter('forumIds', $forumIds)
           ->setParameter('user', $user);
           
        // Si l'utilisateur a activé le filtre, exclure les messages des threads où il ne participe pas
        if ($filterNonParticipating) {
            // Sous-requête pour récupérer les IDs des threads où l'utilisateur a des personnages participants
            $participatingThreadsSubQuery1 = $entityManager->createQueryBuilder();
            $participatingThreadsSubQuery1->select('DISTINCT thread_sub.id')
                ->from('App\Entity\Thread', 'thread_sub')
                ->leftJoin('thread_sub.participants', 'c')
                ->where('c.user = :user');
                
            // Sous-requête pour récupérer les IDs des threads où l'utilisateur a déjà posté
            $participatingThreadsSubQuery2 = $entityManager->createQueryBuilder();
            $participatingThreadsSubQuery2->select('DISTINCT thread_post.id')
                ->from('App\Entity\Thread', 'thread_post')
                ->join('thread_post.posts', 'thread_posts')
                ->where('thread_posts.author = :user');
                
            // N'inclure que les threads épinglés (sticky) ou ceux auxquels l'utilisateur participe
            $qb->andWhere(
                $qb->expr()->orX(
                    't.sticky = true',
                    $qb->expr()->in('t.id', $participatingThreadsSubQuery1->getDQL()),
                    $qb->expr()->in('t.id', $participatingThreadsSubQuery2->getDQL())
                )
            );
        }
        
        $unreadCount = $qb->getQuery()->getSingleScalarResult();
        
        // Retourner si le forum contient des messages non lus et combien
        return new JsonResponse([
            'hasUnread' => $unreadCount > 0,
            'count' => (int)$unreadCount
        ]);
    }

    /**
     * Récupère récursivement tous les IDs des sous-forums d'un forum donné
     */
    private function getAllSubForumIds(int $forumId, EntityManagerInterface $entityManager): array
    {
        $qb = $entityManager->createQueryBuilder();
        $qb->select('f.id')
           ->from('App\Entity\Forum', 'f')
           ->where('f.parent = :parentId')
           ->setParameter('parentId', $forumId);
        
        $subForumIds = array_column($qb->getQuery()->getArrayResult(), 'id');
        
        // Pour chaque sous-forum, récupérer également ses sous-forums
        $allIds = $subForumIds;
        foreach ($subForumIds as $subForumId) {
            $childIds = $this->getAllSubForumIds($subForumId, $entityManager);
            $allIds = array_merge($allIds, $childIds);
        }
        
        return $allIds;
    }

    #[Route('/check-thread/{id}', name: 'app_unread_messages_check_thread', methods: ['GET'])]
    public function checkThread(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['hasUnread' => false]);
        }
        
        // Récupérer la préférence de filtrage de l'utilisateur
        $filterNonParticipating = $user->getFilterNonParticipatingMessages() ?? false;
        
        // Requête de base pour vérifier si le thread contient des messages non lus
        $qb = $entityManager->createQueryBuilder();
        $qb->select('COUNT(p.id)')
           ->from('App\Entity\Post', 'p')
           ->join('p.thread', 't')
           ->leftJoin('App\Entity\ReadPost', 'rp', 'WITH', 'rp.post = p.id AND rp.user = :user')
           ->where('t.id = :threadId')
           ->andWhere('rp.id IS NULL')
           ->andWhere('p.isDraft = false')
           ->andWhere('p.author != :user') // Exclure les messages de l'utilisateur lui-même
           ->setParameter('threadId', $id)
           ->setParameter('user', $user);
           
        // Si l'utilisateur a activé le filtre, vérifier s'il participe à ce thread
        if ($filterNonParticipating) {
            // Vérifier si c'est un thread épinglé (sticky)
            $stickyQuery = $entityManager->createQueryBuilder()
                ->select('t.sticky')
                ->from('App\Entity\Thread', 't')
                ->where('t.id = :threadId')
                ->setParameter('threadId', $id)
                ->getQuery()
                ->getSingleScalarResult();
                
            $isSticky = (bool) $stickyQuery;
            
            // Si ce n'est pas un thread épinglé, vérifier si l'utilisateur y participe
            if (!$isSticky) {
                // Vérifier si l'utilisateur a des personnages participants
                $hasParticipants = $entityManager->createQueryBuilder()
                    ->select('COUNT(c.id)')
                    ->from('App\Entity\Character', 'c')
                    ->join('c.threadsParticipating', 't')
                    ->where('t.id = :threadId')
                    ->andWhere('c.user = :user')
                    ->setParameter('threadId', $id)
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getSingleScalarResult();
                    
                // Vérifier si l'utilisateur a déjà posté dans ce thread
                $hasPosted = $entityManager->createQueryBuilder()
                    ->select('COUNT(p.id)')
                    ->from('App\Entity\Post', 'p')
                    ->join('p.thread', 't')
                    ->where('t.id = :threadId')
                    ->andWhere('p.author = :user')
                    ->setParameter('threadId', $id)
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getSingleScalarResult();
                    
                // Si l'utilisateur ne participe pas à ce thread non-épinglé, retourner 0
                if ($hasParticipants == 0 && $hasPosted == 0) {
                    return new JsonResponse([
                        'hasUnread' => false,
                        'count' => 0
                    ]);
                }
            }
        }
        
        $unreadCount = $qb->getQuery()->getSingleScalarResult();
        
        // Retourner si le thread contient des messages non lus et combien
        return new JsonResponse([
            'hasUnread' => $unreadCount > 0,
            'count' => (int)$unreadCount
        ]);
    }
} 