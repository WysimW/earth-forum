<?php

namespace App\Controller\Api;

use App\Service\S3Service;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Sert les fichiers S3 via l'API (URLs stables, sans query string — compatible CSS background-image).
 */
#[Route('/api/media/file')]
class PublicMediaController extends AbstractController
{
    private const ALLOWED_PREFIX = 'media/';

    public function __construct(
        private readonly S3Service $s3Service,
    ) {
    }

    #[Route('/{path}', name: 'api_public_media_file', requirements: ['path' => '.+'], methods: ['GET', 'OPTIONS'])]
    public function serve(string $path, Request $request): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');

            return $response;
        }

        $key = rawurldecode($path);
        if (!str_starts_with($key, self::ALLOWED_PREFIX) || str_contains($key, '..')) {
            throw new NotFoundHttpException('Fichier introuvable');
        }

        try {
            $object = $this->s3Service->getObjectContent($key);
        } catch (\Throwable) {
            throw new NotFoundHttpException('Fichier introuvable');
        }

        $response = new Response($object['body']);
        $response->headers->set('Content-Type', $object['contentType']);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'public, max-age=86400');

        return $response;
    }
}
