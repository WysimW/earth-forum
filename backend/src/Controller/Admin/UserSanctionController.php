<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\UserSanction;
use App\Form\UserSanctionFormType;
use App\Form\RevokeSanctionFormType;
use App\Repository\UserRepository;
use App\Repository\UserSanctionRepository;
use App\Service\UserSanctionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

#[Route('/admin/sanctions')]
#[IsGranted('ROLE_MODERATOR')]
class UserSanctionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserSanctionRepository $sanctionRepository,
        private UserSanctionService $sanctionService
    ) {
    }

    #[Route('/', name: 'app_admin_sanctions_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Paramètres de pagination et filtres
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $status = $request->query->get('status', 'active');
        $type = $request->query->get('type', null);
        $userId = $request->query->getInt('user', 0);
        
        // Récupérer les sanctions selon les filtres
        $sanctions = $this->sanctionRepository->findByFilters($status, $type, $userId, $page, $limit);
        $totalCount = $this->sanctionRepository->countByFilters($status, $type, $userId);
        
        // Nettoyer les sanctions expirées
        $cleanedCount = $this->sanctionService->cleanExpiredSanctions();
        if ($cleanedCount > 0) {
            $this->addFlash('info', "$cleanedCount sanctions expirées ont été automatiquement désactivées.");
        }
        
        return $this->render('admin/sanction/index.html.twig', [
            'sanctions' => $sanctions,
            'totalCount' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'status' => $status,
            'type' => $type,
            'userId' => $userId,
            'typeChoices' => UserSanction::getTypeChoices()
        ]);
    }

    #[Route('/new', name: 'app_admin_sanctions_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $form = $this->createForm(UserSanctionFormType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'utilisateur directement depuis le formulaire
            $user = $form->get('user')->getData();
            
            try {
                // Déterminer la date d'expiration
                $expiresAt = null;
                $duration = $form->get('duration')->getData();
                if ($duration !== 'permanent') {
                    $interval = new \DateInterval($duration);
                    $expiresAt = new \DateTime();
                    $expiresAt->add($interval);
                }
                
                // Appliquer la sanction
                $this->sanctionService->applySanction(
                    $user,
                    $form->get('type')->getData(),
                    $form->get('reason')->getData(),
                    $expiresAt
                );
                
                $this->addFlash('success', 'Sanction appliquée avec succès.');
                return $this->redirectToRoute('app_admin_sanctions_index');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'application de la sanction: ' . $e->getMessage());
            }
        }
        
        return $this->render('admin/sanction/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'app_admin_sanctions_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $sanction = $this->sanctionRepository->find($id);
        
        if (!$sanction) {
            throw $this->createNotFoundException('La sanction demandée n\'existe pas.');
        }
        
        return $this->render('admin/sanction/show.html.twig', [
            'sanction' => $sanction
        ]);
    }

    #[Route('/{id}/revoke', name: 'app_admin_sanctions_revoke', methods: ['GET', 'POST'])]
    public function revoke(Request $request, int $id): Response
    {
        $sanction = $this->sanctionRepository->find($id);
        
        if (!$sanction) {
            throw $this->createNotFoundException('La sanction demandée n\'existe pas.');
        }
        
        if (!$sanction->isActive()) {
            $this->addFlash('warning', 'Cette sanction n\'est pas active.');
            return $this->redirectToRoute('app_admin_sanctions_show', ['id' => $sanction->getId()]);
        }
        
        $form = $this->createForm(RevokeSanctionFormType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $reason = $form->get('reason')->getData();
            
            try {
                $this->sanctionService->revokeSanction($sanction, $reason);
                $this->addFlash('success', 'Sanction révoquée avec succès.');
                return $this->redirectToRoute('app_admin_sanctions_show', ['id' => $sanction->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la révocation: ' . $e->getMessage());
            }
        }
        
        return $this->render('admin/sanction/revoke.html.twig', [
            'sanction' => $sanction,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/extend', name: 'app_admin_sanctions_extend', methods: ['GET', 'POST'])]
    public function extend(Request $request, int $id): Response
    {
        $sanction = $this->sanctionRepository->find($id);
        
        if (!$sanction) {
            throw $this->createNotFoundException('La sanction demandée n\'existe pas.');
        }
        
        if (!$sanction->isActive()) {
            $this->addFlash('warning', 'Cette sanction n\'est pas active.');
            return $this->redirectToRoute('app_admin_sanctions_show', ['id' => $sanction->getId()]);
        }
        
        if ($sanction->getExpiresAt() === null) {
            $this->addFlash('warning', 'Cette sanction est permanente et ne peut pas être prolongée.');
            return $this->redirectToRoute('app_admin_sanctions_show', ['id' => $sanction->getId()]);
        }
        
        $form = $this->createFormBuilder()
            ->add('duration', ChoiceType::class, [
                'label' => 'Durée supplémentaire',
                'choices' => [
                    '1 jour' => 'P1D',
                    '3 jours' => 'P3D',
                    '1 semaine' => 'P1W',
                    '2 semaines' => 'P2W',
                    '1 mois' => 'P1M'
                ],
                'required' => true
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Raison de la prolongation',
                'required' => true
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            try {
                // Obtenir la date d'expiration actuelle sous forme de timestamp
                $currentTimestamp = $sanction->getExpiresAt()->getTimestamp();
                
                // Calculer la nouvelle date d'expiration en créant un nouvel objet DateTime
                $newExpiresAt = new \DateTime();
                $newExpiresAt->setTimestamp($currentTimestamp);
                
                // Appliquer l'intervalle de temps
                $parts = [];
                switch ($data['duration']) {
                    case 'P1D':
                        $parts = ['days' => 1];
                        break;
                    case 'P3D':
                        $parts = ['days' => 3];
                        break;
                    case 'P1W':
                        $parts = ['days' => 7];
                        break;
                    case 'P2W':
                        $parts = ['days' => 14];
                        break;
                    case 'P1M':
                        $parts = ['months' => 1];
                        break;
                }
                
                foreach ($parts as $part => $value) {
                    $method = "add" . ucfirst($part);
                    if (method_exists($newExpiresAt, $method)) {
                        $newExpiresAt->$method($value);
                    }
                }
                
                $this->sanctionService->extendSanction($sanction, $newExpiresAt, $data['reason']);
                
                $this->addFlash('success', 'Sanction prolongée avec succès.');
                return $this->redirectToRoute('app_admin_sanctions_show', ['id' => $sanction->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la prolongation: ' . $e->getMessage());
            }
        }
        
        return $this->render('admin/sanction/extend.html.twig', [
            'sanction' => $sanction,
            'form' => $form->createView()
        ]);
    }

    #[Route('/user/{id}', name: 'app_admin_user_sanctions', methods: ['GET'])]
    public function userSanctions(int $id): Response
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('L\'utilisateur demandé n\'existe pas.');
        }
        
        $activeSanctions = $this->sanctionRepository->findActiveByUser($user);
        $historySanctions = $this->sanctionRepository->findHistoryByUser($user);
        
        return $this->render('admin/sanction/user_sanctions.html.twig', [
            'user' => $user,
            'activeSanctions' => $activeSanctions,
            'historySanctions' => $historySanctions
        ]);
    }
} 