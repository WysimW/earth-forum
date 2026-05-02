<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\UserDialogueTheme;
use App\Repository\UserDialogueThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user/dialogue-themes')]
class UserDialogueThemeController extends AbstractController
{
    #[Route('', name: 'api_user_dialogue_themes_list', methods: ['GET'])]
    public function list(UserDialogueThemeRepository $themeRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        $themes = $themeRepository->findByUserOrdered($user);

        return new JsonResponse([
            'items' => array_map([$this, 'serializeTheme'], $themes),
            'defaultThemeId' => $this->resolveDefaultThemeId($themes),
        ]);
    }

    #[Route('', name: 'api_user_dialogue_themes_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserDialogueThemeRepository $themeRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        $validationError = $this->validateThemePayload($data);
        if ($validationError !== null) {
            return new JsonResponse(['error' => $validationError], Response::HTTP_BAD_REQUEST);
        }

        $theme = new UserDialogueTheme();
        $theme->setUser($user);
        $theme->setName(trim((string) $data['name']));
        $theme->setColor(strtoupper(trim((string) $data['color'])));
        $theme->setFontFamily($this->normalizeFontFamily($data['fontFamily'] ?? null));
        $theme->setIsBold((bool) ($data['isBold'] ?? false));
        $theme->setIsItalic((bool) ($data['isItalic'] ?? false));
        $theme->setIsDefault((bool) ($data['isDefault'] ?? false));

        if ($theme->isDefault()) {
            $themeRepository->clearDefaultForUser($user);
        }

        $entityManager->persist($theme);
        $entityManager->flush();

        return new JsonResponse([
            'item' => $this->serializeTheme($theme),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_user_dialogue_themes_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        UserDialogueThemeRepository $themeRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        $theme = $themeRepository->find($id);
        if (!$theme || $theme->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Thème introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        }

        $validationError = $this->validateThemePayload($data);
        if ($validationError !== null) {
            return new JsonResponse(['error' => $validationError], Response::HTTP_BAD_REQUEST);
        }

        $theme->setName(trim((string) $data['name']));
        $theme->setColor(strtoupper(trim((string) $data['color'])));
        $theme->setFontFamily($this->normalizeFontFamily($data['fontFamily'] ?? null));
        $theme->setIsBold((bool) ($data['isBold'] ?? false));
        $theme->setIsItalic((bool) ($data['isItalic'] ?? false));
        $theme->setIsDefault((bool) ($data['isDefault'] ?? false));

        if ($theme->isDefault()) {
            $themeRepository->clearDefaultForUser($user, $theme->getId());
        }

        $entityManager->flush();

        return new JsonResponse([
            'item' => $this->serializeTheme($theme),
        ]);
    }

    #[Route('/{id}', name: 'api_user_dialogue_themes_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager, UserDialogueThemeRepository $themeRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        $theme = $themeRepository->find($id);
        if (!$theme || $theme->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Thème introuvable'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($theme);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Thème supprimé']);
    }

    private function validateThemePayload(array $data): ?string
    {
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        if ($name === '') {
            return 'Le nom du thème est requis';
        }
        if (mb_strlen($name) > 120) {
            return 'Le nom du thème est trop long';
        }

        $color = isset($data['color']) ? trim((string) $data['color']) : '';
        if ($color === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return 'La couleur doit être au format hexadécimal (ex: #33CC00)';
        }

        if (array_key_exists('fontFamily', $data) && $data['fontFamily'] !== null) {
            $fontFamily = trim((string) $data['fontFamily']);
            if (mb_strlen($fontFamily) > 80) {
                return 'La police est trop longue';
            }
        }

        return null;
    }

    private function normalizeFontFamily(mixed $fontFamily): ?string
    {
        if (!is_string($fontFamily)) {
            return null;
        }

        $normalized = trim($fontFamily);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param UserDialogueTheme[] $themes
     */
    private function resolveDefaultThemeId(array $themes): ?int
    {
        foreach ($themes as $theme) {
            if ($theme->isDefault()) {
                return $theme->getId();
            }
        }

        return null;
    }

    private function serializeTheme(UserDialogueTheme $theme): array
    {
        return [
            'id' => $theme->getId(),
            'name' => $theme->getName(),
            'color' => $theme->getColor(),
            'fontFamily' => $theme->getFontFamily(),
            'isBold' => $theme->isBold(),
            'isItalic' => $theme->isItalic(),
            'isDefault' => $theme->isDefault(),
            'createdAt' => $theme->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $theme->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
