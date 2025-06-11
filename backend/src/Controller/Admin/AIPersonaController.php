<?php

namespace App\Controller\Admin;

use App\Entity\AIPersona;
use App\Entity\Character;
use App\Entity\Post;
use App\Form\AIPersonaType;
use App\Service\AIPersonaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ai-persona')]
#[IsGranted('ROLE_ANIMATEUR_AI')]
class AIPersonaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AIPersonaService $aiPersonaService
    ) {}

    #[Route('/', name: 'app_ai_persona_index', methods: ['GET'])]
    public function index(): Response
    {
        $personas = $this->entityManager->getRepository(AIPersona::class)->findAll();
        $characters = $this->entityManager->getRepository(Character::class)->findAll();

        return $this->render('admin/ai_persona/index.html.twig', [
            'personas' => $personas,
            'characters' => $characters
        ]);
    }

    #[Route('/new', name: 'app_ai_persona_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $persona = new AIPersona();
        $form = $this->createForm(AIPersonaType::class, $persona);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $persona->setCreator($this->getUser());
            $this->entityManager->persist($persona);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le persona a été créé avec succès.');
            return $this->redirectToRoute('app_ai_persona_index');
        }

        return $this->render('admin/ai_persona/new.html.twig', [
            'persona' => $persona,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ai_persona_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AIPersona $persona): Response
    {
        $form = $this->createForm(AIPersonaType::class, $persona);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le persona a été modifié avec succès.');
            return $this->redirectToRoute('app_ai_persona_index');
        }

        return $this->render('admin/ai_persona/edit.html.twig', [
            'persona' => $persona,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'app_ai_persona_delete', methods: ['POST'])]
    public function delete(Request $request, AIPersona $persona): Response
    {
        if ($this->isCsrfTokenValid('delete'.$persona->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($persona);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le persona a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_ai_persona_index');
    }

    #[Route('/generate-response/{postId}', name: 'app_ai_persona_generate_response', methods: ['POST'])]
    public function generateResponse(int $postId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['personaId'])) {
            return new JsonResponse(['error' => 'Persona ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $post = $this->entityManager->getRepository(Post::class)->find($postId);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $persona = $this->entityManager->getRepository(AIPersona::class)->find($data['personaId']);
        if (!$persona) {
            return new JsonResponse(['error' => 'Persona not found'], Response::HTTP_NOT_FOUND);
        }

        $additionalContext = $data['additionalContext'] ?? null;
        $response = $this->aiPersonaService->generateResponse($persona, $post, $additionalContext);

        return new JsonResponse(['response' => $response]);
    }

    #[Route('/list', name: 'app_ai_persona_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $personas = $this->entityManager->getRepository(AIPersona::class)->findBy(['isActive' => true]);
        
        $data = array_map(function (AIPersona $persona) {
            $character = $persona->getCharacter();
            return [
                'id' => $persona->getId(),
                'name' => $persona->getName(),
                'description' => $persona->getDescription(),
                'character' => [
                    'id' => $character->getId(),
                    'name' => $character->getName(),
                    'race' => $character->getRace(),
                    'class' => $character->getClass(),
                    'alignment' => $character->getAlignment()
                ]
            ];
        }, $personas);

        return new JsonResponse($data);
    }

    #[Route('/create', name: 'app_ai_persona_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['characterId'])) {
            return new JsonResponse(['error' => 'Character ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $character = $this->entityManager->getRepository(Character::class)->find($data['characterId']);
        if (!$character) {
            return new JsonResponse(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $persona = new AIPersona();
        $persona->setName($data['name'] ?? $character->getName() . ' AI');
        $persona->setDescription($data['description'] ?? '');
        $persona->setPersonalityTraits($data['personalityTraits'] ?? []);
        $persona->setKnowledge($data['knowledge'] ?? []);
        $persona->setRelationships($data['relationships'] ?? []);
        $persona->setGoals($data['goals'] ?? []);
        $persona->setSpeechPattern($data['speechPattern'] ?? []);
        $persona->setCharacter($character);
        $persona->setCreator($this->getUser());

        $this->entityManager->persist($persona);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $persona->getId(),
            'name' => $persona->getName(),
            'character' => [
                'id' => $character->getId(),
                'name' => $character->getName()
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/update', name: 'app_ai_persona_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $persona = $this->entityManager->getRepository(AIPersona::class)->find($id);
        if (!$persona) {
            return new JsonResponse(['error' => 'Persona not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $persona->setName($data['name']);
        if (isset($data['description'])) $persona->setDescription($data['description']);
        if (isset($data['personalityTraits'])) $persona->setPersonalityTraits($data['personalityTraits']);
        if (isset($data['knowledge'])) $persona->setKnowledge($data['knowledge']);
        if (isset($data['relationships'])) $persona->setRelationships($data['relationships']);
        if (isset($data['goals'])) $persona->setGoals($data['goals']);
        if (isset($data['speechPattern'])) $persona->setSpeechPattern($data['speechPattern']);
        if (isset($data['isActive'])) $persona->setIsActive($data['isActive']);

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $persona->getId(),
            'name' => $persona->getName(),
            'character' => [
                'id' => $persona->getCharacter()->getId(),
                'name' => $persona->getCharacter()->getName()
            ]
        ]);
    }
} 