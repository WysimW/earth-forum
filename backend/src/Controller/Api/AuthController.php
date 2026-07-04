<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\UserDialogueTheme;
use App\Repository\UserDialogueThemeRepository;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager,
        private UserDialogueThemeRepository $dialogueThemeRepository,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'message' => 'Email et mot de passe requis'
            ], 400);
        }

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'message' => 'Identifiants invalides'
            ], 401);
        }

        $this->touchLastLogin($user);

        // Générer un token JWT avec lexik/jwt-authentication-bundle
        $token = $this->jwtManager->create($user);
        $refreshToken = bin2hex(random_bytes(32));

        // Stocker le refresh token (dans une table dédiée en production)
        // Pour l'instant, on retourne juste un token simple

        $userData = $this->serializeUserData($user);

        return new JsonResponse([
            'token' => $token,
            'refreshToken' => $refreshToken,
            'user' => $userData,
        ]);
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['pseudo'])) {
            return new JsonResponse([
                'message' => 'Email, mot de passe et pseudo requis'
            ], 400);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return new JsonResponse([
                'message' => 'Un utilisateur avec cet email existe déjà'
            ], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setPseudo($data['pseudo']);
        $user->setRoles(['ROLE_USER']);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setAvatar('https://sbcf.fr/wp-content/uploads/2018/03/sbcf-default-avatar.png');

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'message' => 'Erreurs de validation',
                'errors' => $errorMessages
            ], 400);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->touchLastLogin($user);

        // Générer un token JWT avec lexik/jwt-authentication-bundle
        $token = $this->jwtManager->create($user);
        $refreshToken = bin2hex(random_bytes(32));

        $userData = $this->serializeUserData($user);

        return new JsonResponse([
            'token' => $token,
            'refreshToken' => $refreshToken,
            'user' => $userData,
        ], 201);
    }

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['refreshToken'])) {
            return new JsonResponse([
                'message' => 'Refresh token requis'
            ], 400);
        }

        // En production, vérifier le refresh token dans une table dédiée
        // Pour l'instant, on génère un nouveau token
        // TODO: Implémenter la vérification du refresh token

        return new JsonResponse([
            'message' => 'Refresh token non implémenté pour l\'instant'
        ], 501);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // En production, invalider le refresh token
        return new JsonResponse([
            'message' => 'Déconnexion réussie'
        ]);
    }

    #[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'message' => 'Non authentifié'
            ], 401);
        }

        $this->touchLastLogin($user);

        return new JsonResponse($this->serializeUserData($user));
    }

    private function touchLastLogin(User $user): void
    {
        $user->setLastLogin(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    private function serializeUserData(User $user): array
    {
        $dialogueThemes = $this->dialogueThemeRepository->findByUserOrdered($user);

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'pseudo' => $user->getPseudo(),
            'avatar' => $this->s3MediaUrlResolver->resolve($user->getAvatar()),
            'roles' => $user->getRoles(),
            'canCreateFaction' => $user->canCreateFaction(),
            'adminUniverses' => array_map(
                static fn ($universe) => [
                    'id' => $universe->getId(),
                    'name' => $universe->getName(),
                    'slug' => $universe->getSlug(),
                ],
                $user->getAdminUniverses()->toArray()
            ),
            'dialogueThemes' => array_map([$this, 'serializeDialogueTheme'], $dialogueThemes),
            'defaultDialogueThemeId' => $this->resolveDefaultDialogueThemeId($dialogueThemes),
        ];
    }

    /**
     * @param UserDialogueTheme[] $dialogueThemes
     */
    private function resolveDefaultDialogueThemeId(array $dialogueThemes): ?int
    {
        foreach ($dialogueThemes as $dialogueTheme) {
            if ($dialogueTheme->isDefault()) {
                return $dialogueTheme->getId();
            }
        }

        return null;
    }

    private function serializeDialogueTheme(UserDialogueTheme $dialogueTheme): array
    {
        return [
            'id' => $dialogueTheme->getId(),
            'name' => $dialogueTheme->getName(),
            'color' => $dialogueTheme->getColor(),
            'fontFamily' => $dialogueTheme->getFontFamily(),
            'isBold' => $dialogueTheme->isBold(),
            'isItalic' => $dialogueTheme->isItalic(),
            'isDefault' => $dialogueTheme->isDefault(),
        ];
    }

}

