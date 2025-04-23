<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserSanctionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sanctions')]
class UserSanctionController extends AbstractController
{
    public function __construct(
        private UserSanctionRepository $sanctionRepository
    ) {
    }

    #[Route('/my-sanctions', name: 'app_user_my_sanctions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mySanctions(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        $activeSanctions = $this->sanctionRepository->findActiveByUser($user);
        $historySanctions = $this->sanctionRepository->findHistoryByUser($user);
        
        return $this->render('sanctions/my_sanctions.html.twig', [
            'user' => $user,
            'activeSanctions' => $activeSanctions,
            'historySanctions' => $historySanctions
        ]);
    }
} 