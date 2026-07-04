<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('/me', name: 'api_profile_me_update', methods: ['PUT'])]
    public function updateMe(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['message' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('pseudo', $data)) {
            $pseudo = trim((string) $data['pseudo']);
            if ($pseudo === '') {
                return new JsonResponse(['message' => 'Le pseudo ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
            }
            $user->setPseudo($pseudo);
        }

        if (array_key_exists('avatar', $data)) {
            $avatar = $data['avatar'];
            $user->setAvatar(is_string($avatar) && $avatar !== '' ? $avatar : null);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => $user->getPseudo(),
                'avatar' => $this->s3MediaUrlResolver->resolve($user->getAvatar()),
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}
