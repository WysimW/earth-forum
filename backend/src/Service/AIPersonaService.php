<?php

namespace App\Service;

use App\Entity\AIPersona;
use App\Entity\Character;
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
        $prompt = $this->buildPrompt($post, $additionalContext, $persona);

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4-turbo-preview', // Modèle optimisé pour la créativité
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.8, // Légèrement plus créatif pour le RP
                'max_tokens' => 1500, // Plus de place pour des réponses développées
                'presence_penalty' => 0.1, // Encourage la variété
                'frequency_penalty' => 0.1, // Évite les répétitions
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

    private function buildSystemPrompt(AIPersona $persona, ?Character $character): string
    {
        $characterName = $character ? $character->getName() : $persona->getName();
        
        $prompt = "Tu es un assistant IA qui aide à générer des réponses de roleplay pour le personnage {$characterName}.\n\n";
        
        if ($character) {
            $prompt .= "Caractéristiques du personnage :\n";
            if ($character->getBiography()) {
                $prompt .= "- Biographie : " . strip_tags($character->getBiography()) . "\n";
            }
            if ($character->getAge()) {
                $prompt .= "- Âge : {$character->getAge()}\n";
            }
            if ($character->getGender()) {
                $prompt .= "- Genre : {$character->getGender()}\n";
            }
            if ($character->getOccupation()) {
                $prompt .= "- Métier : {$character->getOccupation()}\n";
            }
            if ($character->getMoralAffiliation()) {
                $prompt .= "- Affiliation morale : {$character->getMoralAffiliation()}\n";
            }
            if ($character->getPersonality()) {
                $prompt .= "- Personnalité : " . strip_tags($character->getPersonality()) . "\n";
            }
            if ($character->getAppearance()) {
                $prompt .= "- Apparence : " . strip_tags($character->getAppearance()) . "\n";
            }
            if ($character->getAbilities()) {
                $prompt .= "- Capacités : " . strip_tags($character->getAbilities()) . "\n";
            }
            if ($character->getEquipment()) {
                $prompt .= "- Équipement : " . strip_tags($character->getEquipment()) . "\n";
            }
            $prompt .= "\n";
        }

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
        $prompt .= "1. Reste toujours dans le personnage de {$characterName}\n, sans utiliser les autres personnages sauf si c'est explicitement demandé ou que ce sont des sbires";
        $prompt .= "2. Respecte les traits de personnalité et l'affiliation morale du personnage\n";
        $prompt .= "3. Utilise le style de langage approprié\n";
        $prompt .= "4. Prends en compte l'historique des posts précédents\n";
        $prompt .= "5. Respecte les règles du forum et du roleplay\n";
        $prompt .= "6. Évite le meta-gaming et le power-playing\n";
        $prompt .= "7. Reste cohérent avec le contexte narratif\n";
        $prompt .= "8. Génère une réponse en HTML formaté (utilise <p>, <strong>, <em>, etc.)\n";

        return $prompt;
    }

    private function buildPrompt(Post $post, ?string $additionalContext = null, ?AIPersona $persona = null): string
    {
        $thread = $post->getThread();
        
        $prompt = "Contexte de la discussion :\n";
        $prompt .= "Thread : {$thread->getTitle()}\n";
        
        if ($thread->getDescription()) {
            $prompt .= "Description du thread : " . strip_tags($thread->getDescription()) . "\n";
        }
        
        $prompt .= "\n=== HISTORIQUE COMPLET DU THREAD (dans l'ordre chronologique) ===\n\n";
        
        // Récupérer tous les posts du thread dans l'ordre chronologique
        $allPosts = $this->entityManager->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.thread = :thread')
            ->andWhere('p.isDraft = false')
            ->setParameter('thread', $thread)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Limiter à 15 derniers messages pour éviter de dépasser les limites de tokens
        $recentPosts = array_slice($allPosts, -15);
        
        foreach ($recentPosts as $index => $threadPost) {
            $postNumber = $index + 1;
            $postContent = strip_tags($threadPost->getContent());
            
            // Informations sur l'auteur
            if ($threadPost->getCharacter()) {
                $author = "🎭 " . $threadPost->getCharacter()->getName();
                if ($threadPost->getCharacter()->getMoralAffiliation()) {
                    $author .= " (" . $threadPost->getCharacter()->getMoralAffiliation() . ")";
                }
            } else {
                $author = "👤 " . $threadPost->getAuthor()->getPseudo() . " (HRP)";
            }
            
            $prompt .= "📝 Message #{$postNumber} - {$author}\n";
            $prompt .= "🕒 {$threadPost->getCreatedAt()->format('d/m/Y H:i')}\n";
            $prompt .= "💬 {$postContent}\n\n";
        }
        
        $prompt .= "=== FIN DE L'HISTORIQUE ===\n\n";
        
        // Ajouter l'historique des réponses précédentes de ce persona
        if ($persona && $persona->getPostHistory()) {
            $prompt .= "=== HISTORIQUE DE VOS RÉPONSES PRÉCÉDENTES ===\n";
            $recentHistory = array_slice($persona->getPostHistory(), -5); // 5 dernières réponses
            foreach ($recentHistory as $entry) {
                $prompt .= "• Post #{$entry['post_id']} - {$entry['timestamp']}\n";
                $prompt .= "  Votre réponse : " . strip_tags($entry['response']) . "\n\n";
            }
            $prompt .= "=== FIN DE VOTRE HISTORIQUE ===\n\n";
        }
        
        // Contexte narratif actuel
        if ($persona && $persona->getDecodedNarrativeContext()) {
            $context = $persona->getDecodedNarrativeContext();
            $prompt .= "=== CONTEXTE NARRATIF ACTUEL ===\n";
            $prompt .= "Dernière mise à jour : {$context['last_update']}\n";
            if (isset($context['data']['last_response'])) {
                $prompt .= "Votre dernière action : " . strip_tags($context['data']['last_response']) . "\n";
            }
            $prompt .= "=== FIN DU CONTEXTE ===\n\n";
        }

        if ($additionalContext) {
            $prompt .= "=== INSTRUCTIONS SUPPLÉMENTAIRES ===\n";
            $prompt .= "{$additionalContext}\n\n";
        }

        $prompt .= "=== MISSION ===\n";
        $prompt .= "Analysez tout l'historique ci-dessus et générez une réponse appropriée qui :\n";
        $prompt .= "1. S'inscrit naturellement dans la continuité narrative\n";
        $prompt .= "2. Respecte les interactions précédentes avec les autres personnages\n";
        $prompt .= "3. Maintient la cohérence avec vos actions passées\n";
        $prompt .= "4. Fait avancer l'intrigue de manière créative\n";
        $prompt .= "5. Est formatée en HTML avec <p>, <strong>, <em>, etc.\n";
        $prompt .= "6. Respecte la chronologie et les événements établis\n\n";
        
        $prompt .= "Répondez UNIQUEMENT avec le contenu de votre message RP, sans explications meta.";

        return $prompt;
    }
} 