<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {
    }

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->dashboardService->buildDashboard($user, [
            'universe' => $request->query->get('universe'),
            'threadType' => $request->query->get('threadType'),
            'threadStatus' => $request->query->get('threadStatus'),
            'limit' => $request->query->getInt('limit', 5),
        ]);

        return new JsonResponse($data);
    }
}
