<?php

namespace App\Controller\Admin;

use App\Entity\AIPersona;
use App\Entity\Character;
use App\Entity\Post;
use App\Form\AIPersonaType;
use App\Service\AIPersonaService;
use App\Service\ClaudeAIPersonaService;
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
        private AIPersonaService $aiPersonaService,
        private ClaudeAIPersonaService $claudeAIPersonaService
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
        $useClaudeModel = $data['useClaudeModel'] ?? false;
        
        if ($useClaudeModel) {
            $response = $this->claudeAIPersonaService->generateResponse($persona, $post, $additionalContext);
        } else {
            $response = $this->aiPersonaService->generateResponse($persona, $post, $additionalContext);
        }

        return new JsonResponse([
            'response' => $response,
            'model_used' => $useClaudeModel ? 'claude-3-opus' : 'gpt-4-turbo'
        ]);
    }

    #[Route('/list', name: 'app_ai_persona_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $personas = $this->entityManager->getRepository(AIPersona::class)->findBy(['isActive' => true]);
        
        $data = array_map(function (AIPersona $persona) {
            $character = $persona->getCharacter();
            $characterData = null;
            
            if ($character) {
                $characterData = [
                    'id' => $character->getId(),
                    'name' => $character->getName(),
                    'avatar' => $character->getAvatarCircleUrl(),
                    'occupation' => $character->getOccupation(),
                    'age' => $character->getAge(),
                    'moralAffiliation' => $character->getMoralAffiliation()
                ];
            }
            
            return [
                'id' => $persona->getId(),
                'name' => $persona->getName(),
                'description' => $persona->getDescription(),
                'personalityTraits' => $persona->getPersonalityTraits(),
                'character' => $characterData
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

    #[Route('/publish-response/{threadId}', name: 'app_ai_persona_publish_response', methods: ['POST'])]
    public function publishResponse(int $threadId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['personaId']) || !isset($data['content'])) {
            return new JsonResponse(['error' => 'Persona ID and content are required'], Response::HTTP_BAD_REQUEST);
        }

        $thread = $this->entityManager->getRepository(\App\Entity\Thread::class)->find($threadId);
        if (!$thread) {
            return new JsonResponse(['error' => 'Thread not found'], Response::HTTP_NOT_FOUND);
        }

        $persona = $this->entityManager->getRepository(AIPersona::class)->find($data['personaId']);
        if (!$persona) {
            return new JsonResponse(['error' => 'Persona not found'], Response::HTTP_NOT_FOUND);
        }

        $character = $persona->getCharacter();
        if (!$character) {
            return new JsonResponse(['error' => 'Character not found for this persona'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier les permissions
        if (!$thread->isOpen() && !$this->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(['error' => 'This thread is closed'], Response::HTTP_FORBIDDEN);
        }

        // Créer le nouveau post
        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser()); // L'utilisateur actuel est l'auteur
        $post->setCharacter($character); // Le personnage lié au persona
        $post->setContent($data['content']);
        $post->setType('roleplay'); // Post RP
        
        // Définir si c'est un brouillon ou non
        $isDraft = $data['isDraft'] ?? false;
        $post->setIsDraft($isDraft);

        // Gérer les NPCs si fournis
        if (isset($data['npcIds']) && is_array($data['npcIds'])) {
            foreach ($data['npcIds'] as $npcId) {
                $npc = $this->entityManager->getRepository(\App\Entity\Npc::class)->find($npcId);
                if ($npc && $thread->getNpcs()->contains($npc)) {
                    $post->addNpc($npc);
                }
            }
        }

        // Ajouter le personnage comme participant s'il n'y est pas déjà
        if ($thread->getType() === 'roleplay' && !$thread->getParticipants()->contains($character)) {
            if ($thread->isFull()) {
                return new JsonResponse(['error' => 'This RP scene has reached its maximum number of participants'], Response::HTTP_BAD_REQUEST);
            }
            
            $thread->addParticipant($character);
        }

        // Mettre à jour la date de dernière activité du thread
        $thread->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        // Marquer le post comme lu pour l'auteur
        if (!$isDraft) {
            $readPostRepo = $this->entityManager->getRepository(\App\Entity\ReadPost::class);
            $readPostRepo->markAsRead($this->getUser(), $post);
        }

        $message = $isDraft ? 'Réponse sauvegardée en brouillon' : 'Réponse publiée avec succès';

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'postId' => $post->getId(),
            'isDraft' => $isDraft,
            'redirectUrl' => $this->generateUrl('app_thread_show', [
                'universeSlug' => $thread->getForum()->getUniverse()->getSlug(),
                'id' => $thread->getId()
            ])
        ]);
    }
} 