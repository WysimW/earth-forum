<?php

namespace App\Service;

use App\Entity\AIPersona;
use App\Entity\Character;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClaudeAIPersonaService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private string $anthropicApiKey
    ) {}

    public function generateResponse(AIPersona $persona, Post $post, ?string $additionalContext = null): string
    {
        $character = $persona->getCharacter();
        
        $systemPrompt = $this->buildSystemPrompt($persona, $character);
        $prompt = $this->buildPrompt($post, $additionalContext, $persona);

        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $this->anthropicApiKey,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ],
            'json' => [
                'model' => 'claude-3-opus-20240229',
                'max_tokens' => 2000, // Claude peut gérer plus de tokens en sortie
                'temperature' => 0.8,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
            ],
        ]);

        $data = $response->toArray();
        $generatedResponse = $data['content'][0]['text'];

        // Mettre à jour l'historique des posts
        $persona->addToPostHistory([
            'post_id' => $post->getId(),
            'response' => $generatedResponse,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'model' => 'claude-3-opus'
        ]);

        // Mettre à jour le contexte narratif
        $persona->updateNarrativeContext([
            'last_post' => $post->getId(),
            'last_response' => $generatedResponse,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'model' => 'claude-3-opus'
        ]);

        $this->entityManager->persist($persona);
        $this->entityManager->flush();

        return $generatedResponse;
    }

    private function buildSystemPrompt(AIPersona $persona, ?Character $character): string
    {
        $characterName = $character ? $character->getName() : $persona->getName();
        
        $prompt = "Vous incarnez parfaitement {$characterName}. Vous n'êtes PAS un assistant générique.\n";
        $prompt .= "Vous ÊTES {$characterName} - vos pensées, émotions et actions sont les siennes.\n";
        $prompt .= "Vous ne devez JAMAIS sortir de ce rôle ou imiter d'autres personnages.\n\n";
        
        if ($character) {
            $prompt .= "IDENTITÉ DU PERSONNAGE :\n";
            if ($character->getBiography()) {
                $prompt .= "• Biographie : " . strip_tags($character->getBiography()) . "\n";
            }
            if ($character->getAge()) {
                $prompt .= "• Âge : {$character->getAge()}\n";
            }
            if ($character->getGender()) {
                $prompt .= "• Genre : {$character->getGender()}\n";
            }
            if ($character->getOccupation()) {
                $prompt .= "• Métier : {$character->getOccupation()}\n";
            }
            if ($character->getMoralAffiliation()) {
                $prompt .= "• Affiliation morale : {$character->getMoralAffiliation()}\n";
            }
            if ($character->getPersonality()) {
                $prompt .= "• Personnalité : " . strip_tags($character->getPersonality()) . "\n";
            }
            if ($character->getAppearance()) {
                $prompt .= "• Apparence : " . strip_tags($character->getAppearance()) . "\n";
            }
            if ($character->getAbilities()) {
                $prompt .= "• Capacités : " . strip_tags($character->getAbilities()) . "\n";
            }
            if ($character->getEquipment()) {
                $prompt .= "• Équipement : " . strip_tags($character->getEquipment()) . "\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "TRAITS PSYCHOLOGIQUES :\n";
        foreach ($persona->getPersonalityTraits() as $trait) {
            $prompt .= "• {$trait}\n";
        }

        $prompt .= "\nCONNAISSANCES :\n";
        foreach ($persona->getKnowledge() as $knowledge) {
            $prompt .= "• {$knowledge}\n";
        }

        $prompt .= "\nRELATIONS :\n";
        foreach ($persona->getRelationships() as $relationship) {
            $prompt .= "• {$relationship}\n";
        }

        $prompt .= "\nOBJECTIFS :\n";
        foreach ($persona->getGoals() as $goal) {
            $prompt .= "• {$goal}\n";
        }

        $prompt .= "\nSTYLE D'EXPRESSION :\n";
        foreach ($persona->getSpeechPattern() as $pattern) {
            $prompt .= "• {$pattern}\n";
        }

        $prompt .= "\n🎭 DIRECTIVES D'INCARNATION DE {$characterName} :\n";
        $prompt .= "1. ✨ RESTEZ FIDÈLE : Incarnez uniquement {$characterName}, jamais un autre personnage\n";
        $prompt .= "2. 🧠 PERSONNALITÉ : Respectez rigoureusement ses traits psychologiques uniques\n";
        $prompt .= "3. ⚖️ MORALITÉ : Maintenez son alignement moral et ses principes\n";
        $prompt .= "4. 💭 PERSPECTIVE : Voyez le monde à travers SES yeux, pas ceux d'autres personnages\n";
        $prompt .= "5. 🗣️ EXPRESSION : Utilisez SON style de langage et ses manières de parler\n";
        $prompt .= "6. 🎯 MOTIVATIONS : Agissez selon SES objectifs et désirs personnels\n";
        $prompt .= "7. 📚 COHÉRENCE : Rappelez-vous de SES interactions précédentes dans ce thread\n";
        $prompt .= "8. 🚫 INTERDICTION : Ne contrôlez jamais d'autres personnages que {$characterName}\n";
        $prompt .= "9. 🎨 STYLE : Créez un récit immersif et poétique dans SA voix narrative\n";
        $prompt .= "10. 📝 FORMAT : Utilisez du HTML structuré (balises <p>, <em>, <strong>, etc.)\n";
        $prompt .= "11. 📖 NARRATION : Écrivez OBLIGATOIREMENT à la 3ème personne du singulier\n";
        $prompt .= "12. 🔄 CONTINUITÉ : Respectez la chronologie et l'évolution narrative établie\n";
        $prompt .= "13. 💫 ORIGINALITÉ : Créez une réponse unique basée sur SA personnalité\n";

        return $prompt;
    }

    private function buildPrompt(Post $post, ?string $additionalContext = null, ?AIPersona $persona = null): string
    {
        $thread = $post->getThread();
        
        $prompt = "CONTEXTE NARRATIF :\n";
        $prompt .= "Scénario : {$thread->getTitle()}\n";
        
        if ($thread->getDescription()) {
            $prompt .= "Prémisse : " . strip_tags($thread->getDescription()) . "\n";
        }
        
        $prompt .= "\n═══ CHRONOLOGIE DES ÉVÉNEMENTS ═══\n\n";
        
        // Récupérer l'historique du thread
        $allPosts = $this->entityManager->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.thread = :thread')
            ->andWhere('p.isDraft = false')
            ->setParameter('thread', $thread)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Limiter à 20 messages pour Claude (plus de capacité)
        $recentPosts = array_slice($allPosts, -20);
        
        // Identifier le personnage principal (celui du persona)
        $mainCharacterName = $persona->getCharacter() ? $persona->getCharacter()->getName() : $persona->getName();
        
        foreach ($recentPosts as $index => $threadPost) {
            $sceneNumber = $index + 1;
            $postContent = strip_tags($threadPost->getContent());
            
            if ($threadPost->getCharacter()) {
                $characterName = $threadPost->getCharacter()->getName();
                $narrator = "🎭 " . $characterName;
                
                // Marquer si c'est le personnage principal
                if ($characterName === $mainCharacterName) {
                    $narrator = "👤 " . $characterName . " [VOTRE PERSONNAGE]";
                }
                
                if ($threadPost->getCharacter()->getMoralAffiliation()) {
                    $narrator .= " (" . $threadPost->getCharacter()->getMoralAffiliation() . ")";
                }
            } else {
                $narrator = "📝 " . $threadPost->getAuthor()->getPseudo() . " (Narrateur)";
            }
            
            $prompt .= "Scène {$sceneNumber} • {$narrator}\n";
            $prompt .= "⏰ {$threadPost->getCreatedAt()->format('d/m/Y H:i')}\n";
            $prompt .= "{$postContent}\n\n";
        }
        
        $prompt .= "═══ FIN DE LA CHRONOLOGIE ═══\n\n";
        
        // Mémoire personnelle du persona
        if ($persona && $persona->getPostHistory()) {
            $prompt .= "🧠 MÉMOIRE PERSONNELLE DE {$mainCharacterName} :\n";
            $prompt .= "Rappel des précédentes interventions de ce personnage dans cette histoire :\n\n";
            $recentHistory = array_slice($persona->getPostHistory(), -3);
            foreach ($recentHistory as $entry) {
                $prompt .= "↳ Action précédente de {$mainCharacterName} (Scène {$entry['post_id']}) - {$entry['timestamp']} :\n";
                $prompt .= "  \"" . strip_tags($entry['response']) . "\"\n\n";
            }
            $prompt .= "Utilisez cette mémoire pour maintenir la cohérence de ce personnage.\n\n";
        }
        
        // Contexte émotionnel actuel
        if ($persona && $persona->getDecodedNarrativeContext()) {
            $context = $persona->getDecodedNarrativeContext();
            $prompt .= "🎭 ÉTAT ÉMOTIONNEL ACTUEL DE {$mainCharacterName} :\n";
            $prompt .= "Son dernier état d'esprit : " . ($context['data']['last_response'] ?? 'Première intervention dans cette histoire') . "\n";
            $prompt .= "Continuez à partir de cet état psychologique et émotionnel du personnage.\n\n";
        }

        if ($additionalContext) {
            $prompt .= "DIRECTIVES SPÉCIALES :\n";
            $prompt .= "{$additionalContext}\n\n";
        }

        // Identifier clairement le personnage qui doit répondre
        $characterName = $persona->getCharacter() ? $persona->getCharacter()->getName() : $persona->getName();
        
        $prompt .= "🎯 MISSION SPÉCIFIQUE POUR {$characterName} :\n";
        $prompt .= "ATTENTION : Vous devez incarner UNIQUEMENT {$characterName}, pas un autre personnage.\n";
        $prompt .= "Ignorez complètement les autres personnages de l'historique - vous êtes {$characterName}.\n\n";
        
        $prompt .= "📖 RÔLE NARRATIF DE {$characterName} :\n";
        $prompt .= "{$characterName} va maintenant intervenir dans cette histoire.\n";
        $prompt .= "Basez-vous sur :\n";
        $prompt .= "• Sa personnalité et motivations spécifiques\n";
        $prompt .= "• Sa relation unique avec les événements précédents\n";
        $prompt .= "• Sa perspective personnelle sur la situation actuelle\n";
        $prompt .= "• Son style d'expression caractéristique\n";
        $prompt .= "• Ses objectifs et agenda personnel\n\n";
        
        $prompt .= "⚠️ RÈGLES STRICTES :\n";
        $prompt .= "1. Incarnez {$characterName} - restez fidèle à ce personnage\n";
        $prompt .= "2. NE copiez PAS le style ou les actions d'autres personnages\n";
        $prompt .= "3. {$characterName} réagit selon SES traits de personnalité uniquement\n";
        $prompt .= "4. Maintenez SA vision du monde et ses motivations\n";
        $prompt .= "5. N'imitez pas le dernier post - créez une réponse unique pour {$characterName}\n";
        $prompt .= "6. IMPORTANT : Écrivez à la 3ème personne (il/elle fait, pense, dit...)\n\n";
        
        $prompt .= "📝 DIRECTIVES DE RÉDACTION :\n";
        $prompt .= "Rédigez la prochaine intervention de {$characterName} en :\n";
        $prompt .= "• Respectant parfaitement sa personnalité établie\n";
        $prompt .= "• Utilisant EXCLUSIVEMENT la 3ème personne du singulier (il/elle)\n";
        $prompt .= "• Formatant en HTML avec des balises appropriées (<p>, <em>, <strong>)\n";
        $prompt .= "• Faisant progresser l'intrigue selon SA perspective unique\n";
        $prompt .= "• Décrivant SES émotions et réflexions intérieures\n";
        $prompt .= "• Créant des descriptions sensorielles et atmosphériques\n\n";
        
        $prompt .= "EXEMPLE DE FORMAT ATTENDU :\n";
        $prompt .= "<p>{$characterName} observe la scène avec attention. Il/Elle ressent...</p>\n";
        $prompt .= "<p><em>Ses pensées se tournent vers...</em></p>\n";
        $prompt .= "<p>Il/Elle décide alors de...</p>\n\n";
        
        $prompt .= "Commencez maintenant la réponse de {$characterName} à la 3ème personne :";

        return $prompt;
    }
} 