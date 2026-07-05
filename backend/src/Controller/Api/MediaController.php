<?php

namespace App\Controller\Api;

use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use App\Service\S3MediaUrlResolver;
use App\Service\S3Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/media')]
class MediaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MediaRepository $mediaRepository,
        private S3Service $s3Service,
        private S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('', name: 'api_admin_media_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier que l'utilisateur est admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $type = $request->query->get('type'); // 'image', 'document', etc.
        $media = $this->mediaRepository->findByType($type);

        // Si aucun média trouvé, retourner un tableau vide
        if (empty($media)) {
            return new JsonResponse(['media' => []]);
        }

        $data = array_map(fn (Media $media) => $this->serializeMedia($media), $media);

        $this->entityManager->flush();

        return new JsonResponse(['media' => $data]);
    }

    #[Route('/upload', name: 'api_admin_media_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier que l'utilisateur est admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('file');

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
            $filename = uniqid('media_', true) . '.' . $extension;
            $s3Key = 'media/' . date('Y/m') . '/' . $filename;

            // Upload vers S3
            $tempPath = $file->getPathname();
            $url = $this->s3Service->uploadFile($tempPath, $s3Key, $mimeType);

            // Si ACL est private, générer une URL pré-signée
            // AWS limite les URLs présignées à 7 jours maximum (604800 secondes)
            if ($_ENV['AWS_S3_ACL'] === 'private') {
                $url = $this->s3Service->getPresignedUrl($s3Key, 604800); // 7 jours (maximum autorisé par AWS)
            }

            // Créer l'entité Media
            $media = new Media();
            $media->setFilename($filename);
            $media->setOriginalFilename($originalFilename);
            $media->setMimeType($mimeType);
            $media->setSize($file->getSize());
            $media->setUrl($this->s3MediaUrlResolver->normalizeStoredUrl($url));
            $media->setS3Key($s3Key);
            $media->setType('image');
            
            /** @var User $user */
            $user = $this->getUser();
            $media->setUploadedBy($user);

            $this->entityManager->persist($media);
            $this->entityManager->flush();

            return new JsonResponse($this->serializeMedia($media), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de l\'upload: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'api_admin_media_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier que l'utilisateur est admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $media = $this->mediaRepository->find($id);

        if (!$media) {
            return new JsonResponse(['error' => 'Média non trouvé'], Response::HTTP_NOT_FOUND);
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

    #[Route('/refresh-expired-urls', name: 'api_admin_media_refresh_expired', methods: ['POST'])]
    public function refreshExpiredUrls(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier que l'utilisateur est admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        try {
            // Récupérer tous les médias
            $allMedia = $this->mediaRepository->findAll();
            $refreshedCount = 0;
            $errors = [];

            foreach ($allMedia as $media) {
                $normalizedUrl = $this->s3MediaUrlResolver->normalizeStoredUrl($media->getUrl());
                if ($normalizedUrl !== $media->getUrl()) {
                    $media->setUrl($normalizedUrl);
                    $this->entityManager->persist($media);
                    $refreshedCount++;
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'refreshed' => $refreshedCount,
                'total' => count($allMedia),
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de la régénération: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMedia(Media $media): array
    {
        $normalizedUrl = $this->s3MediaUrlResolver->normalizeStoredUrl($media->getUrl());
        if ($normalizedUrl !== $media->getUrl()) {
            $media->setUrl($normalizedUrl);
            $this->entityManager->persist($media);
        }

        return [
            'id' => $media->getId(),
            'filename' => $media->getFilename(),
            'originalFilename' => $media->getOriginalFilename(),
            'mimeType' => $media->getMimeType(),
            'size' => $media->getSize(),
            'url' => $this->s3MediaUrlResolver->resolve($media->getUrl()),
            'storageUrl' => $media->getUrl(),
            'type' => $media->getType(),
            'uploadedAt' => $media->getUploadedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}

