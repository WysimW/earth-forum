<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BreadcrumbService;

#[Route('/locations')]
class LocationController extends AbstractController
{
    private $breadcrumbService;

    public function __construct(BreadcrumbService $breadcrumbService)
    {
        $this->breadcrumbService = $breadcrumbService;
    }

    #[Route('/', name: 'app_location_index', methods: ['GET'])]
    public function index(LocationRepository $locationRepository): Response
    {
        $locations = $locationRepository->findBy(['isPublic' => true], ['name' => 'ASC']);
        
        $breadcrumbs = [
            ['name' => 'Accueil', 'url' => $this->generateUrl('app_roleplay')],
            ['name' => 'Lieux', 'url' => '']
        ];
        
        return $this->render('location/index.html.twig', [
            'locations' => $locations,
            'breadcrumbs' => $breadcrumbs
        ]);
    }
    
    #[Route('/{id}/{slug}', name: 'app_location_show', methods: ['GET'])]
    public function show(Location $location, string $slug): Response
    {
        // Vérifier si le lieu est public ou si l'utilisateur est administrateur
        if (!$location->isIsPublic() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce lieu.');
        }
        
        // Vérifier que le slug est correct, sinon rediriger vers l'URL correcte
        if ($location->getSlug() !== $slug) {
            return $this->redirectToRoute('app_location_show', [
                'id' => $location->getId(),
                'slug' => $location->getSlug()
            ]);
        }
        
        // Construction des fils d'Ariane
        $breadcrumbs = [
            ['name' => 'Accueil', 'url' => $this->generateUrl('app_roleplay')],
            ['name' => 'Lieux', 'url' => $this->generateUrl('app_location_index')]
        ];
        
        // Ajouter l'univers si disponible
        if ($location->getUniverse()) {
            $breadcrumbs[] = [
                'name' => $location->getUniverse()->getName(),
                'url' => $this->generateUrl('app_univers_show', ['slug' => $location->getUniverse()->getSlug()])
            ];
        }
        
        // Ajouter le lieu parent si disponible
        if ($location->getParent()) {
            $breadcrumbs[] = [
                'name' => $location->getParent()->getName(),
                'url' => $this->generateUrl('app_location_show', [
                    'id' => $location->getParent()->getId(),
                    'slug' => $location->getParent()->getSlug()
                ])
            ];
        }
        
        // Ajouter le lieu courant
        $breadcrumbs[] = ['name' => $location->getName(), 'url' => ''];
        
        return $this->render('location/show.html.twig', [
            'location' => $location,
            'breadcrumbs' => $breadcrumbs
        ]);
    }
} 