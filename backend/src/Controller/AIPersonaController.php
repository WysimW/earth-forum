<?php

namespace App\Controller;

use App\Entity\AIPersona;
use App\Entity\Post;
use App\Entity\Thread;
use App\Repository\AIPersonaRepository;
use App\Repository\ThreadRepository;
use App\Service\ClaudeAIPersonaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AIPersonaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AIPersonaRepository $aiPersonaRepository,
        private ThreadRepository $threadRepository,
        private ClaudeAIPersonaService $claudeService
    ) {
    }

    #[Route('/ai-persona/list', name: 'app_ai_persona_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $personas = $this->aiPersonaRepository->findBy([
            'user' => $user,
            'isActive' => true
        ]);

        $data = [];
        foreach ($personas as $persona) {
            $character = $persona->getCharacter();
            $data[] = [
                'id' => $persona->getId(),
                'name' => $persona->getName(),
                'character' => [
                    'id' => $character->getId(),
                    'name' => $character->getName(),
                    'race' => $character->getRace(),
                    'class' => $character->getClass()
                ]
            ];
        }

        return $this->json($data);
    }

    #[Route('/ai-persona/generate-response/{threadId}', name: 'app_ai_persona_generate_response', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateResponse(int $threadId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['personaId'])) {
            return $this->json(['error' => 'ID du persona requis'], Response::HTTP_BAD_REQUEST);
        }

        $persona = $this->aiPersonaRepository->find($data['personaId']);
        if (!$persona || $persona->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Persona non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $thread = $this->threadRepository->find($threadId);
        if (!$thread) {
            return $this->json(['error' => 'Thread non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les permissions
        if (!$this->isGranted('PARTICIPATE', $thread)) {
            return $this->json(['error' => 'Permission refusée'], Response::HTTP_FORBIDDEN);
        }

        try {
            $additionalContext = $data['additionalContext'] ?? '';
            $response = $this->claudeService->generateResponse($persona, $thread, $additionalContext);

            return $this->json([
                'success' => true,
                'response' => $response,
                'persona' => [
                    'id' => $persona->getId(),
                    'name' => $persona->getName(),
                    'character' => $persona->getCharacter()->getName()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/ai-persona/publish-response/{threadId}', name: 'app_ai_persona_publish_response', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publishResponse(int $threadId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['personaId'], $data['content'])) {
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $persona = $this->aiPersonaRepository->find($data['personaId']);
        if (!$persona || $persona->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Persona non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $thread = $this->threadRepository->find($threadId);
        if (!$thread) {
            return $this->json(['error' => 'Thread non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les permissions
        if (!$this->isGranted('PARTICIPATE', $thread)) {
            return $this->json(['error' => 'Permission refusée'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que le thread n'est pas fermé
        if ($thread->getStatus() === 'closed') {
            return $this->json(['error' => 'Ce thread est fermé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $isDraft = $data['isDraft'] ?? false;
            $content = $data['content'];

            // Créer le nouveau post
            $post = new Post();
            $post->setThread($thread);
            $post->setAuthor($this->getUser()); // L'utilisateur actuel est l'auteur
            $post->setCharacter($persona->getCharacter()); // Le personnage du persona
            $post->setContent($content);
            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setUpdatedAt(new \DateTimeImmutable());
            
            if ($isDraft) {
                $post->setStatus('draft');
            } else {
                $post->setStatus('published');
                
                // Mettre à jour les statistiques du thread
                $thread->setUpdatedAt(new \DateTimeImmutable());
                $thread->setPostCount($thread->getPostCount() + 1);
                
                // Ajouter le personnage comme participant s'il ne l'est pas déjà
                if (!$thread->getParticipants()->contains($persona->getCharacter())) {
                    $thread->addParticipant($persona->getCharacter());
                }
            }

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => $isDraft ? 'Réponse sauvegardée en brouillon' : 'Réponse publiée avec succès',
                'postId' => $post->getId()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la publication: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 