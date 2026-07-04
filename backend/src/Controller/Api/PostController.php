<?php

namespace App\Controller\Api;

use App\Entity\Character;
use App\Entity\Npc;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/posts')]
class PostController extends AbstractController
{
    #[Route('', name: 'api_posts_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $securityUser = $this->getUser();
        if (!$securityUser instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['threadId'], $data['content'])) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        $thread = $em->getRepository(Thread::class)->find((int) $data['threadId']);
        if (!$thread) {
            return new JsonResponse(['error' => 'Thread introuvable'], Response::HTTP_NOT_FOUND);
        }
        $forumStatus = $thread->getForum()?->getStatus();
        if ($forumStatus !== 'open') {
            return new JsonResponse([
                'error' => $forumStatus === 'archived'
                    ? 'Le forum de ce thread est archivé'
                    : 'Le forum de ce thread est fermé, vous ne pouvez pas publier'
            ], Response::HTTP_FORBIDDEN);
        }

        $hasCharacterId = isset($data['characterId']) && (int) $data['characterId'] > 0;
        $hasNpcId = isset($data['npcId']) && (int) $data['npcId'] > 0;

        if ($hasCharacterId && $hasNpcId) {
            return new JsonResponse(['error' => 'Indiquez soit characterId soit npcId'], Response::HTTP_BAD_REQUEST);
        }

        $character = !$hasNpcId ? $this->resolveCharacterForPost($thread, $data, $em, $securityUser) : null;
        $npc = $hasNpcId ? $this->resolveFactionNpcForMissionPost($thread, $data, $em) : null;

        if ($thread->isRoleplay() && !$character instanceof Character && !$npc instanceof Npc) {
            return new JsonResponse(['error' => 'characterId ou npcId requis pour un thread RP'], Response::HTTP_BAD_REQUEST);
        }

        if (!$thread->isRoleplay()) {
            $character = null;
            $npc = null;
        }

        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($securityUser);
        $normalizedContent = $this->normalizePostHtml((string) $data['content']);
        if ($normalizedContent === '') {
            return new JsonResponse(['error' => 'Le post est vide après nettoyage'], Response::HTTP_BAD_REQUEST);
        }
        $post->setContent($normalizedContent);
        $post->setType($thread->isRoleplay() ? 'roleplay' : 'normal');

        if ($character) {
            $post->setCharacter($character);
            $thread->addParticipant($character);
        }

        if ($npc) {
            $post->addNpc($npc);
            $thread->addNpc($npc);
        }

        if (isset($data['quotedPostId'])) {
            $quotedPost = $em->getRepository(Post::class)->find((int) $data['quotedPostId']);
            if ($quotedPost && $quotedPost->getThread()?->getId() === $thread->getId()) {
                $post->setQuotedPost($quotedPost);
            }
        }

        $em->persist($post);
        $em->flush();

        return new JsonResponse([
            'status' => 'Post créé',
            'postId' => $post->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_posts_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $securityUser = $this->getUser();
        if (!$securityUser instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        $post = $em->getRepository(Post::class)->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post introuvable'], Response::HTTP_NOT_FOUND);
        }

        $canModerate = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MODERATOR');
        if (!$canModerate && $post->getAuthor()?->getId() !== $securityUser->getId()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['content'])) {
            return new JsonResponse(['error' => 'content requis'], Response::HTTP_BAD_REQUEST);
        }

        $normalizedContent = $this->normalizePostHtml((string) $data['content']);
        if ($normalizedContent === '') {
            return new JsonResponse(['error' => 'Le post est vide après nettoyage'], Response::HTTP_BAD_REQUEST);
        }
        $post->setContent($normalizedContent);
        $post->setEditedAt(new \DateTime());
        $em->flush();

        return new JsonResponse(['status' => 'Post modifié']);
    }

    #[Route('/{id}', name: 'api_posts_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $securityUser = $this->getUser();
        if (!$securityUser instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        $post = $em->getRepository(Post::class)->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post introuvable'], Response::HTTP_NOT_FOUND);
        }

        $canModerate = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MODERATOR');
        if (!$canModerate && $post->getAuthor()?->getId() !== $securityUser->getId()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($post);
        $em->flush();

        return new JsonResponse(['status' => 'Post supprimé']);
    }

    #[Route('/{id}/quote', name: 'api_posts_quote', methods: ['GET'])]
    public function quote(int $id, EntityManagerInterface $em): JsonResponse
    {
        $post = $em->getRepository(Post::class)->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post introuvable'], Response::HTTP_NOT_FOUND);
        }

        $authorName = $post->getCharacter()?->getName();
        if (!$authorName && $post->getNpcs()->count() > 0) {
            $firstNpc = $post->getNpcs()->first();
            $authorName = $firstNpc instanceof Npc ? ($firstNpc->getName() ?: null) : null;
        }
        $authorName = $authorName ?: ($post->getAuthor()?->getPseudo() ?: 'Anonyme');
        $quoted = sprintf(
            '<blockquote><p><strong>%s a dit :</strong></p>%s</blockquote><p><br></p>',
            htmlspecialchars($authorName, ENT_QUOTES),
            $post->getContent() ?? ''
        );

        return new JsonResponse([
            'content' => $quoted,
            'quotedPostId' => $post->getId(),
        ]);
    }

    private function resolveCharacterForPost(Thread $thread, array $data, EntityManagerInterface $em, User $user): ?Character
    {
        if (!$thread->isRoleplay()) {
            return null;
        }

        $characterId = isset($data['characterId']) ? (int) $data['characterId'] : 0;
        if ($characterId <= 0) {
            return null;
        }

        $character = $em->getRepository(Character::class)->find($characterId);
        if (!$character instanceof Character) {
            return null;
        }

        if ($character->isEventCharacter()) {
            $eventActivity = $character->getEventActivity();
            if (!$eventActivity) {
                return null;
            }

            $isThreadLinked = false;
            foreach ($thread->getRpActivities() as $linkedActivity) {
                if ((int) $linkedActivity->getId() === (int) $eventActivity->getId()) {
                    $isThreadLinked = true;
                    break;
                }
            }
            if (!$isThreadLinked) {
                return null;
            }

            $isAllowedUser = (int) ($eventActivity->getCreatedBy()?->getId() ?? 0) === (int) $user->getId();
            if (!$isAllowedUser) {
                foreach ($eventActivity->getAllowedUsers() as $allowedUser) {
                    if ((int) $allowedUser->getId() === (int) $user->getId()) {
                        $isAllowedUser = true;
                        break;
                    }
                }
            }

            return $isAllowedUser ? $character : null;
        }

        if (!$character->getUser() || $character->getUser()->getId() !== $user->getId()) {
            return null;
        }

        return $character;
    }

    private function resolveFactionNpcForMissionPost(Thread $thread, array $data, EntityManagerInterface $em): ?Npc
    {
        $npcId = isset($data['npcId']) ? (int) $data['npcId'] : 0;
        if ($npcId <= 0 || !$thread->isRoleplay()) {
            return null;
        }

        $npc = $em->getRepository(Npc::class)->find($npcId);
        if (!$npc instanceof Npc || $npc->getStatus() !== Npc::STATUS_VALIDATED) {
            return null;
        }

        foreach ($thread->getRpActivities() as $activity) {
            if ($activity->getKind() !== \App\Entity\RpActivity::KIND_MISSION || !$activity->getFaction()) {
                continue;
            }

            if ($activity->getUniverse()?->getId() !== $npc->getUniverse()?->getId()) {
                continue;
            }

            foreach ($npc->getFactionsRelation() as $faction) {
                if ($faction && (int) $faction->getId() === (int) $activity->getFaction()?->getId()) {
                    return $npc;
                }
            }
        }

        return null;
    }

    private function normalizePostHtml(string $html): string
    {
        $normalized = trim($html);
        if ($normalized === '') {
            return '';
        }

        $normalized = $this->stripBackgroundStyles($normalized);

        // Supprime les paragraphes/divs vides uniquement composés d'espaces, &nbsp; ou <br>.
        $pattern = '/<(p|div)>(?:\s|&nbsp;|<br\s*\/?>)*<\/\1>/i';
        do {
            $before = $normalized;
            $normalized = preg_replace($pattern, '', $normalized) ?? $normalized;
        } while ($normalized !== $before);

        // Nettoie les sauts de ligne inutiles résiduels.
        $normalized = trim($normalized);

        return $normalized;
    }

    private function stripBackgroundStyles(string $html): string
    {
        // Retire l'attribut bgcolor hérité d'anciens éditeurs.
        $cleaned = preg_replace('/\sbgcolor\s*=\s*("|\')[^"\']*\1/i', '', $html) ?? $html;

        // Retire les déclarations CSS background/background-color dans style="...".
        $cleaned = preg_replace_callback(
            '/style\s*=\s*("|\')(.*?)\1/i',
            static function (array $matches): string {
                $quote = $matches[1];
                $styleContent = $matches[2];
                $declarations = array_filter(array_map('trim', explode(';', $styleContent)));

                $keptDeclarations = array_values(array_filter(
                    $declarations,
                    static function (string $declaration): bool {
                        $parts = explode(':', $declaration, 2);
                        if (count($parts) < 2) {
                            return false;
                        }

                        $property = strtolower(trim($parts[0]));

                        return $property !== 'background' && $property !== 'background-color';
                    }
                ));

                if (count($keptDeclarations) === 0) {
                    return '';
                }

                return sprintf('style=%s%s%s', $quote, implode('; ', $keptDeclarations), $quote);
            },
            $cleaned
        ) ?? $cleaned;

        return $cleaned;
    }
}
