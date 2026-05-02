<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\UniversRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UniversRepository $universRepository
    ) {
    }

    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Vérification de l'authentification et des droits admin
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $search = $request->query->get('search', '');
        $roleFilter = $request->query->get('role');
        $statusFilter = $request->query->get('status');

        $users = $this->userRepository->findAll();

        // Filtrer les utilisateurs
        $filteredUsers = array_filter($users, function (User $user) use ($search, $roleFilter, $statusFilter) {
            // Filtre de recherche
            if ($search) {
                $searchLower = strtolower($search);
                $matchesSearch = 
                    str_contains(strtolower($user->getEmail()), $searchLower) ||
                    str_contains(strtolower($user->getPseudo()), $searchLower);
                if (!$matchesSearch) {
                    return false;
                }
            }

            // Filtre par rôle
            if ($roleFilter) {
                $userRoles = $user->getRoles();
                $isAdminUser = in_array('ROLE_ADMIN', $userRoles, true) || in_array('ROLE_SUPER_ADMIN', $userRoles, true);
                if ($roleFilter === 'admin' && !$isAdminUser) {
                    return false;
                }
                if ($roleFilter === 'super_admin' && !in_array('ROLE_SUPER_ADMIN', $userRoles, true)) {
                    return false;
                }
                if ($roleFilter === 'moderator' && !in_array('ROLE_MODERATOR', $userRoles, true)) {
                    return false;
                }
                if ($roleFilter === 'user' && $isAdminUser) {
                    if (in_array('ROLE_MODERATOR', $userRoles, true)) {
                        return false;
                    }
                    return false;
                }
            }

            // Filtre par statut
            if ($statusFilter) {
                $isActive = $user->isActive();
                if ($statusFilter === 'active' && !$isActive) {
                    return false;
                }
                if ($statusFilter === 'inactive' && $isActive) {
                    return false;
                }
            }

            return true;
        });

        $data = array_map(function (User $user) {
            $roles = $user->getRoles();
            $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $roles, true);
            $isAdmin = in_array('ROLE_ADMIN', $roles, true) || $isSuperAdmin;
            $isModerator = in_array('ROLE_MODERATOR', $roles, true);
            
            return [
                'id' => $user->getId(),
                'pseudo' => $user->getPseudo(),
                'email' => $user->getEmail(),
                'avatar' => $user->getAvatar(),
                'roles' => $roles,
                'role' => $isSuperAdmin ? 'super_admin' : ($isAdmin ? 'admin' : ($isModerator ? 'moderator' : 'user')),
                'status' => $user->isActive() ? 'active' : 'inactive',
                'isActive' => $user->isActive(),
                'canCreateFaction' => $user->canCreateFaction(),
                'adminUniverseIds' => array_map(
                    static fn ($universe) => $universe->getId(),
                    $user->getAdminUniverses()->toArray()
                ),
                'adminUniverses' => array_map(
                    static fn ($universe) => [
                        'id' => $universe->getId(),
                        'name' => $universe->getName(),
                    ],
                    $user->getAdminUniverses()->toArray()
                ),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                'lastLogin' => $user->getLastLogin()?->format('Y-m-d H:i:s'),
            ];
        }, array_values($filteredUsers));

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'api_admin_users_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $this->userRepository->find($id);
        if (!$targetUser) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $roles = $targetUser->getRoles();
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $roles, true);
        $isAdmin = in_array('ROLE_ADMIN', $roles, true) || $isSuperAdmin;
        $isModerator = in_array('ROLE_MODERATOR', $roles, true);

        $data = [
            'id' => $targetUser->getId(),
            'pseudo' => $targetUser->getPseudo(),
            'email' => $targetUser->getEmail(),
            'avatar' => $targetUser->getAvatar(),
            'roles' => $roles,
            'role' => $isSuperAdmin ? 'super_admin' : ($isAdmin ? 'admin' : ($isModerator ? 'moderator' : 'user')),
            'status' => $targetUser->isActive() ? 'active' : 'inactive',
            'isActive' => $targetUser->isActive(),
            'canCreateFaction' => $targetUser->canCreateFaction(),
            'adminUniverseIds' => array_map(
                static fn ($universe) => $universe->getId(),
                $targetUser->getAdminUniverses()->toArray()
            ),
            'adminUniverses' => array_map(
                static fn ($universe) => [
                    'id' => $universe->getId(),
                    'name' => $universe->getName(),
                ],
                $targetUser->getAdminUniverses()->toArray()
            ),
            'createdAt' => $targetUser->getCreatedAt()?->format('Y-m-d H:i:s'),
            'lastLogin' => $targetUser->getLastLogin()?->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'api_admin_users_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $this->userRepository->find($id);
        if (!$targetUser) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['pseudo'])) {
            $targetUser->setPseudo($data['pseudo']);
        }

        if (isset($data['email'])) {
            $targetUser->setEmail($data['email']);
        }

        if (isset($data['avatar'])) {
            $targetUser->setAvatar($data['avatar']);
        }

        if (isset($data['roles'])) {
            $requestedRoles = array_values(array_unique(array_filter((array) $data['roles'], static fn ($role) => is_string($role) && $role !== 'ROLE_USER')));
            $isActorSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
            $targetHasPrivilegedRole = in_array('ROLE_ADMIN', $targetUser->getRoles(), true) || in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles(), true);

            if (
                !$isActorSuperAdmin
                && (
                    in_array('ROLE_ADMIN', $requestedRoles, true)
                    || in_array('ROLE_SUPER_ADMIN', $requestedRoles, true)
                    || $targetHasPrivilegedRole
                )
            ) {
                return new JsonResponse(['error' => 'Seul un super administrateur peut attribuer les rôles admin ou super admin'], Response::HTTP_FORBIDDEN);
            }

            $targetUser->setRoles($requestedRoles);
        }

        if (array_key_exists('adminUniverseIds', $data)) {
            $targetUser->clearAdminUniverses();

            foreach ((array) $data['adminUniverseIds'] as $universeId) {
                $universe = $this->universRepository->find((int) $universeId);
                if ($universe) {
                    $targetUser->addAdminUniverse($universe);
                }
            }
        }

        $effectiveRoles = isset($data['roles']) ? (array) $data['roles'] : $targetUser->getRoles();
        $needsUniverseScope = in_array('ROLE_ADMIN', $effectiveRoles, true) || in_array('ROLE_MODERATOR', $effectiveRoles, true);
        if (!$needsUniverseScope) {
            $targetUser->clearAdminUniverses();
        }

        if (isset($data['isActive'])) {
            $targetUser->setIsActive((bool)$data['isActive']);
        }
        if (isset($data['canCreateFaction'])) {
            $targetUser->setCanCreateFaction((bool) $data['canCreateFaction']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($targetUser, $data['password']);
            $targetUser->setPassword($hashedPassword);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur mis à jour avec succès']);
    }

    #[Route('/{id}', name: 'api_admin_users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $this->userRepository->find($id);
        if (!$targetUser) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Empêcher la suppression de soi-même
        if ($targetUser->getId() === $user->getId()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas supprimer votre propre compte'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($targetUser);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur supprimé avec succès']);
    }

    #[Route('', name: 'api_admin_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['pseudo'])) {
            return new JsonResponse(['error' => 'Email, mot de passe et pseudo requis'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Un utilisateur avec cet email existe déjà'], Response::HTTP_BAD_REQUEST);
        }

        $newUser = new User();
        $newUser->setEmail($data['email']);
        $newUser->setPseudo($data['pseudo']);
        $newUser->setPassword($this->passwordHasher->hashPassword($newUser, $data['password']));
        $requestedRoles = array_values(array_unique(array_filter((array) ($data['roles'] ?? []), static fn ($role) => is_string($role) && $role !== 'ROLE_USER')));
        $isActorSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        if (!$isActorSuperAdmin && (in_array('ROLE_ADMIN', $requestedRoles, true) || in_array('ROLE_SUPER_ADMIN', $requestedRoles, true))) {
            return new JsonResponse(['error' => 'Seul un super administrateur peut attribuer les rôles admin ou super admin'], Response::HTTP_FORBIDDEN);
        }
        $newUser->setRoles($requestedRoles);
        $newUser->setAvatar($data['avatar'] ?? 'https://sbcf.fr/wp-content/uploads/2018/03/sbcf-default-avatar.png');
        $newUser->setIsActive($data['isActive'] ?? true);
        $newUser->setCanCreateFaction((bool) ($data['canCreateFaction'] ?? false));
        $newUser->setCreatedAt(new \DateTimeImmutable());

        if (array_key_exists('adminUniverseIds', $data)) {
            foreach ((array) $data['adminUniverseIds'] as $universeId) {
                $universe = $this->universRepository->find((int) $universeId);
                if ($universe) {
                    $newUser->addAdminUniverse($universe);
                }
            }
        }

        $needsUniverseScope = in_array('ROLE_ADMIN', $newUser->getRoles(), true) || in_array('ROLE_MODERATOR', $newUser->getRoles(), true);
        if (!$needsUniverseScope) {
            $newUser->clearAdminUniverses();
        }

        $this->entityManager->persist($newUser);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $newUser->getId(),
            'pseudo' => $newUser->getPseudo(),
            'email' => $newUser->getEmail(),
            'roles' => $newUser->getRoles(),
            'isActive' => $newUser->isActive(),
            'canCreateFaction' => $newUser->canCreateFaction(),
            'adminUniverseIds' => array_map(
                static fn ($universe) => $universe->getId(),
                $newUser->getAdminUniverses()->toArray()
            ),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/toggle-status', name: 'api_admin_users_toggle_status', methods: ['POST'])]
    public function toggleStatus(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $this->userRepository->find($id);
        if (!$targetUser) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Empêcher la désactivation de soi-même
        if ($targetUser->getId() === $user->getId()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas désactiver votre propre compte'], Response::HTTP_BAD_REQUEST);
        }

        $targetUser->setIsActive(!$targetUser->isActive());
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Statut mis à jour avec succès',
            'isActive' => $targetUser->isActive(),
        ]);
    }
}

