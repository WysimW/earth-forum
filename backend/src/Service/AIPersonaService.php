<?php

namespace App\Service;

use App\Entity\AIPersona;
use App\Entity\Post;
use App\Entity\Thread;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIPersonaService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private string $openaiApiKey
    ) {}

    public function generateResponse(AIPersona $persona, Post $post, ?string $additionalContext = null): string
    {
        $character = $persona->getCharacter();
        
        $systemPrompt = $this->buildSystemPrompt($persona, $character);
        $prompt = $this->buildPrompt($post, $additionalContext);

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ],
        ]);

        $data = $response->toArray();
        $generatedResponse = $data['choices'][0]['message']['content'];

        // Mettre à jour l'historique des posts
        $persona->addToPostHistory([
            'post_id' => $post->getId(),
            'response' => $generatedResponse,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);

        // Mettre à jour le contexte narratif
        $persona->updateNarrativeContext([
            'last_post' => $post->getId(),
            'last_response' => $generatedResponse,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);

        $this->entityManager->persist($persona);
        $this->entityManager->flush();

        return $generatedResponse;
    }

    private function buildSystemPrompt(AIPersona $persona, RoleplayCharacter $character): string
    {
        $prompt = "Tu es un assistant IA qui aide à générer des réponses de roleplay pour le personnage {$character->getName()}.\n\n";
        $prompt .= "Caractéristiques du personnage :\n";
        $prompt .= "- Description : {$character->getDescription()}\n";
        $prompt .= "- Âge : {$character->getAge()}\n";
        $prompt .= "- Genre : {$character->getGender()}\n";
        $prompt .= "- Race : {$character->getRace()}\n";
        $prompt .= "- Classe : {$character->getClass()}\n";
        $prompt .= "- Alignement : {$character->getAlignment()}\n\n";

        $prompt .= "Traits de personnalité :\n";
        foreach ($persona->getPersonalityTraits() as $trait) {
            $prompt .= "- {$trait}\n";
        }

        $prompt .= "\nConnaissances :\n";
        foreach ($persona->getKnowledge() as $knowledge) {
            $prompt .= "- {$knowledge}\n";
        }

        $prompt .= "\nRelations :\n";
        foreach ($persona->getRelationships() as $relationship) {
            $prompt .= "- {$relationship}\n";
        }

        $prompt .= "\nObjectifs :\n";
        foreach ($persona->getGoals() as $goal) {
            $prompt .= "- {$goal}\n";
        }

        $prompt .= "\nStyle de langage :\n";
        foreach ($persona->getSpeechPattern() as $pattern) {
            $prompt .= "- {$pattern}\n";
        }

        $prompt .= "\nRègles importantes :\n";
        $prompt .= "1. Reste toujours dans le personnage de {$character->getName()}\n";
        $prompt .= "2. Respecte l'alignement et les traits de personnalité du personnage\n";
        $prompt .= "3. Utilise le style de langage approprié\n";
        $prompt .= "4. Prends en compte l'historique des posts précédents\n";
        $prompt .= "5. Respecte les règles du forum et du roleplay\n";
        $prompt .= "6. Évite le meta-gaming et le power-playing\n";
        $prompt .= "7. Reste cohérent avec le contexte narratif\n";

        return $prompt;
    }

    private function buildPrompt(Post $post, ?string $additionalContext = null): string
    {
        $prompt = "Contexte de la discussion :\n";
        $prompt .= "Thread : {$post->getThread()->getTitle()}\n";
        $prompt .= "Dernier message : {$post->getContent()}\n";

        if ($additionalContext) {
            $prompt .= "\nInstructions supplémentaires :\n{$additionalContext}\n";
        }

        $prompt .= "\nGénère une réponse appropriée pour le personnage, en respectant son style et sa personnalité.";

        return $prompt;
    }
} 