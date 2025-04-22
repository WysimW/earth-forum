<?php

namespace App\Controller;

use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use App\Repository\UniversRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\BreadcrumbService;

#[Route('/locations')]
class LocationController extends AbstractController
{
    private $breadcrumbService;
    private $slugger;

    public function __construct(BreadcrumbService $breadcrumbService, SluggerInterface $slugger)
    {
        $this->breadcrumbService = $breadcrumbService;
        $this->slugger = $slugger;
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

    #[Route('/create-ajax', name: 'app_location_create_ajax', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createAjax(Request $request, EntityManagerInterface $entityManager, UniversRepository $universRepository): JsonResponse
    {
        // Vérifier le jeton CSRF
        if (!$this->isCsrfTokenValid('location_create', $request->headers->get('X-CSRF-TOKEN'))) {
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
        
        // Créer le nouveau lieu
        $location = new Location();
        $location->setName($data['name']);
        $location->setUniverse($universe);
        
        // Générer le slug à partir du nom
        $slug = strtolower($this->slugger->slug($data['name']));
        $location->setSlug($slug);
        
        // Ajouter la description si présente
        if (isset($data['description']) && !empty($data['description'])) {
            $location->setDescription($data['description']);
        }
        
        // Enregistrer le lieu
        $entityManager->persist($location);
        $entityManager->flush();
        
        // Retourner la réponse avec les données du lieu créé
        return new JsonResponse([
            'success' => true,
            'location' => [
                'id' => $location->getId(),
                'name' => $location->getName(),
                'slug' => $location->getSlug()
            ]
        ]);
    }

    #[Route('/new', name: 'app_location_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $location = new Location();
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($location);
            $entityManager->flush();

            return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('location/new.html.twig', [
            'location' => $location,
            'form' => $form,
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

    #[Route('/{id}/edit', name: 'app_location_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('location/edit.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_location_delete', methods: ['POST'])]
    public function delete(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$location->getId(), $request->request->get('_token'))) {
            $entityManager->remove($location);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
    }
} 