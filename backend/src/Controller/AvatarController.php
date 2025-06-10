<?php

namespace App\Controller;

use App\Entity\Character;
use App\Service\AvatarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/characters/{id}/avatar')]
class AvatarController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvatarService $avatarService
    ) {}

    #[Route('', name: 'app_character_avatar_manage', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function manage(Character $character): Response
    {
        // Vérifier que l'utilisateur peut modifier ce personnage
        if ($character->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier l\'avatar de ce personnage.');
        }

        return $this->render('avatar/manage.html.twig', [
            'character' => $character,
        ]);
    }

    #[Route('/upload', name: 'app_character_avatar_upload_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function upload(Character $character, Request $request): JsonResponse
    {
        // Vérifier que l'utilisateur peut modifier ce personnage
        if ($character->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $file = $request->files->get('avatar');

        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier fourni'], 400);
        }

        // Vérifications immédiates
        if (!$file instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Type de fichier invalide'], 400);
        }

        if (!$file->isValid()) {
            return new JsonResponse(['error' => 'Erreur lors de l\'upload : ' . $file->getErrorMessage()], 400);
        }

        // Validation de la taille (5MB max)
        $maxFileSize = 5 * 1024 * 1024;
        $fileSize = $file->getSize();
        if ($fileSize === false || $fileSize > $maxFileSize) {
            return new JsonResponse(['error' => 'Le fichier est trop volumineux. Taille maximum : 5MB'], 400);
        }

        // Validation du type MIME
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return new JsonResponse(['error' => 'Type de fichier non autorisé. Formats acceptés : JPEG, PNG, GIF, WebP'], 400);
        }

        try {
            // Supprimer l'ancien avatar si il existe
            if ($character->getAvatarFilename()) {
                $this->avatarService->delete($character->getAvatarFilename());
            }

            $result = $this->avatarService->upload($file, $character->getName());
            
            // Mettre à jour le personnage (sans sauvegarder encore)
            $character->setAvatarFilename($result['filename']);
            
            return new JsonResponse([
                'success' => true,
                'filename' => $result['filename'],
                'path' => $result['path'],
                'message' => 'Avatar uploadé avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/crop', name: 'app_character_avatar_crop_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function crop(Character $character, Request $request): JsonResponse
    {
        // Vérifier que l'utilisateur peut modifier ce personnage
        if ($character->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['filename']) || !isset($data['cropData'])) {
            return new JsonResponse(['error' => 'Données manquantes'], 400);
        }

        try {
            $versions = $this->avatarService->cropImage($data['filename'], $data['cropData']);
            
            // Sauvegarder les données de recadrage et finaliser
            $character->setAvatarCrop($data['cropData']);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'versions' => $versions,
                'message' => 'Avatar recadré et sauvegardé avec succès',
                'avatarUrl' => $character->getAvatarUrl()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/set-url', name: 'app_character_avatar_set_url', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function setUrl(Character $character, Request $request): JsonResponse
    {
        // Vérifier que l'utilisateur peut modifier ce personnage
        if ($character->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $url = $data['url'] ?? '';

        try {
            // Supprimer l'avatar uploadé si il existe
            if ($character->getAvatarFilename()) {
                $this->avatarService->delete($character->getAvatarFilename());
                $character->setAvatarFilename(null);
                $character->setAvatarCrop(null);
            }

            // Définir la nouvelle URL
            $character->setAvatar($url ?: null);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Avatar mis à jour avec succès',
                'avatarUrl' => $character->getAvatarUrl()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/remove', name: 'app_character_avatar_remove', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function remove(Character $character, Request $request): JsonResponse
    {
        // Vérifier que l'utilisateur peut modifier ce personnage
        if ($character->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('remove_avatar_' . $character->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Token de sécurité invalide'], 400);
        }

        try {
            // Supprimer le fichier si il existe
            if ($character->getAvatarFilename()) {
                $this->avatarService->delete($character->getAvatarFilename());
            }

            // Nettoyer toutes les données d'avatar
            $character->setAvatar(null);
            $character->setAvatarFilename(null);
            $character->setAvatarCrop(null);
            
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Avatar supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
} 