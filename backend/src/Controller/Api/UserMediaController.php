<?php

namespace App\Controller\Api;

use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use App\Service\S3Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/media')]
class UserMediaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MediaRepository $mediaRepository,
        private S3Service $s3Service
    ) {
    }

    #[Route('', name: 'api_media_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $type = $request->query->get('type'); // 'image', 'document', etc.
        $originalsOnly = $request->query->getBoolean('originalsOnly', false); // Filtrer uniquement les images originales
        
        // Récupérer les médias de l'utilisateur uniquement
        $criteria = ['uploadedBy' => $user];
        if ($type) {
            $criteria['type'] = $type;
        }
        if ($originalsOnly) {
            $criteria['parentMedia'] = null; // Uniquement les images sans parent (images originales)
        }
        $media = $this->mediaRepository->findBy($criteria, ['uploadedAt' => 'DESC']);

        // Si aucun média trouvé, retourner un tableau vide
        if (empty($media)) {
            return new JsonResponse(['media' => []]);
        }

        $data = array_map(function (Media $media) {
            // Régénérer l'URL si elle est expirée
            if ($media->isUrlExpired() && $media->getS3Key()) {
                try {
                    $newUrl = $this->s3Service->getPresignedUrl($media->getS3Key(), 604800); // 7 jours
                    $media->setUrl($newUrl);
                    $this->entityManager->persist($media);
                } catch (\Exception $e) {
                    // En cas d'erreur, continuer avec l'URL existante
                }
            }

            return [
                'id' => $media->getId(),
                'filename' => $media->getFilename(),
                'originalFilename' => $media->getOriginalFilename(),
                'mimeType' => $media->getMimeType(),
                'size' => $media->getSize(),
                'url' => $media->getUrl(),
                'type' => $media->getType(),
                'uploadedAt' => $media->getUploadedAt()?->format('Y-m-d H:i:s'),
                'parentMediaId' => $media->getParentMedia()?->getId(),
            ];
        }, $media);

        // Sauvegarder les URLs régénérées
        $this->entityManager->flush();

        return new JsonResponse(['media' => $data]);
    }

    #[Route('/upload', name: 'api_media_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $file = $request->files->get('file');
        $parentMediaId = $request->request->get('parentMediaId'); // ID de l'image source pour les versions croppées

        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier fourni'], Response::HTTP_BAD_REQUEST);
        }

        // Validation du type de fichier
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ];

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return new JsonResponse(
                ['error' => 'Type de fichier non autorisé. Seules les images sont acceptées.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validation de la taille (max 20MB)
        $maxSize = 20 * 1024 * 1024; // 20MB
        if ($file->getSize() > $maxSize) {
            return new JsonResponse(
                ['error' => 'Le fichier est trop volumineux. Taille maximale: 20MB'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // Générer un nom de fichier unique
            $originalFilename = $file->getClientOriginalName();
            $extension = $file->guessExtension() ?: 'bin';
            $filename = uniqid('user_' . $user->getId() . '_', true) . '.' . $extension;
            $s3Key = 'media/' . date('Y/m') . '/' . $filename;

            // Upload vers S3
            $tempPath = $file->getPathname();
            $url = $this->s3Service->uploadFile($tempPath, $s3Key, $mimeType);

            // Déterminer le type de média
            $type = 'image';
            if (str_starts_with($mimeType, 'image/')) {
                $type = 'image';
            }

            // Créer l'entité Media
            $media = new Media();
            $media->setFilename($filename);
            $media->setOriginalFilename($originalFilename);
            $media->setMimeType($mimeType);
            $media->setSize($file->getSize());
            $media->setUrl($url);
            $media->setS3Key($s3Key);
            $media->setType($type);
            $media->setUploadedBy($user);

            // Si parentMediaId est fourni, lier l'image croppée à son image source
            if ($parentMediaId) {
                $parentMedia = $this->mediaRepository->find($parentMediaId);
                if ($parentMedia && $parentMedia->getUploadedBy() === $user) {
                    $media->setParentMedia($parentMedia);
                }
            }

            $this->entityManager->persist($media);
            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $media->getId(),
                'filename' => $media->getFilename(),
                'originalFilename' => $media->getOriginalFilename(),
                'mimeType' => $media->getMimeType(),
                'size' => $media->getSize(),
                'url' => $media->getUrl(),
                'type' => $media->getType(),
                'parentMediaId' => $media->getParentMedia()?->getId(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de l\'upload: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'api_media_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $media = $this->mediaRepository->find($id);

        if (!$media) {
            return new JsonResponse(['error' => 'Média non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est propriétaire du média
        if ($media->getUploadedBy() !== $user) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        try {
            // Supprimer de S3
            $this->s3Service->deleteFile($media->getS3Key());

            // Supprimer de la base de données
            $this->entityManager->remove($media);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de la suppression: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}/cropped', name: 'api_media_cropped', methods: ['GET'])]
    public function getCroppedVersions(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $originalMedia = $this->mediaRepository->find($id);

        if (!$originalMedia) {
            return new JsonResponse(['error' => 'Média non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est propriétaire du média original
        if ($originalMedia->getUploadedBy() !== $user) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer toutes les versions croppées de cette image
        $croppedVersions = $this->mediaRepository->findBy(
            ['parentMedia' => $originalMedia, 'uploadedBy' => $user],
            ['uploadedAt' => 'DESC']
        );

        $data = array_map(function (Media $media) {
            // Régénérer l'URL si elle est expirée
            if ($media->isUrlExpired() && $media->getS3Key()) {
                try {
                    $newUrl = $this->s3Service->getPresignedUrl($media->getS3Key(), 604800); // 7 jours
                    $media->setUrl($newUrl);
                    $this->entityManager->persist($media);
                } catch (\Exception $e) {
                    // En cas d'erreur, continuer avec l'URL existante
                }
            }

            return [
                'id' => $media->getId(),
                'filename' => $media->getFilename(),
                'originalFilename' => $media->getOriginalFilename(),
                'mimeType' => $media->getMimeType(),
                'size' => $media->getSize(),
                'url' => $media->getUrl(),
                'type' => $media->getType(),
                'uploadedAt' => $media->getUploadedAt()?->format('Y-m-d H:i:s'),
                'parentMediaId' => $media->getParentMedia()?->getId(),
            ];
        }, $croppedVersions);

        // Sauvegarder les URLs régénérées
        $this->entityManager->flush();

        return new JsonResponse(['media' => $data]);
    }

    #[Route('/refresh-expired-urls', name: 'api_media_refresh_expired', methods: ['POST'])]
    public function refreshExpiredUrls(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Récupérer uniquement les médias de l'utilisateur
            $userMedia = $this->mediaRepository->findBy(['uploadedBy' => $user]);
            $refreshedCount = 0;
            $errors = [];

            foreach ($userMedia as $media) {
                if ($media->isUrlExpired() && $media->getS3Key()) {
                    try {
                        $newUrl = $this->s3Service->getPresignedUrl($media->getS3Key(), 604800); // 7 jours
                        $media->setUrl($newUrl);
                        $this->entityManager->persist($media);
                        $refreshedCount++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'id' => $media->getId(),
                            'filename' => $media->getFilename(),
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'refreshed' => $refreshedCount,
                'total' => count($userMedia),
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de la régénération: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

