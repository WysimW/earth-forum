<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/media/proxy', priority: 10)]
#[IsGranted('ROLE_USER')]
class UserMediaProxyController extends AbstractController
{
    #[Route('', name: 'api_media_proxy', methods: ['GET', 'OPTIONS'], priority: 10)]
    public function proxy(Request $request): Response
    {
        // Gérer les requêtes OPTIONS pour CORS
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            return $response;
        }

        // Vérification de l'authentification (utilisateur authentifié, pas besoin d'admin)
        $user = $this->getUser();
        if (!$user) {
            return new Response('Accès refusé', Response::HTTP_FORBIDDEN);
        }

        $url = $request->query->get('url');
        
        if (!$url) {
            return new Response('URL manquante', Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que l'URL est bien une URL S3 valide ou une URL d'image valide
        $allowedDomains = ['s3.amazonaws.com', 'amazonaws.com', 's3.'];
        $isValidUrl = false;
        foreach ($allowedDomains as $domain) {
            if (strpos($url, $domain) !== false) {
                $isValidUrl = true;
                break;
            }
        }

        if (!$isValidUrl) {
            return new Response('URL invalide', Response::HTTP_BAD_REQUEST);
        }

        try {
            // Utiliser curl pour charger l'image avec les bons en-têtes
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            
            if ($imageData === false || $httpCode !== 200) {
                return new Response('Erreur lors du chargement de l\'image (HTTP ' . $httpCode . ')', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Déterminer le type MIME depuis le Content-Type de curl ou depuis l'URL
            $mimeType = $contentType ?: 'image/png';
            if (!$mimeType || $mimeType === 'application/octet-stream') {
                // Fallback sur l'extension de l'URL
                if (strpos($url, '.jpg') !== false || strpos($url, '.jpeg') !== false) {
                    $mimeType = 'image/jpeg';
                } elseif (strpos($url, '.gif') !== false) {
                    $mimeType = 'image/gif';
                } elseif (strpos($url, '.webp') !== false) {
                    $mimeType = 'image/webp';
                } elseif (strpos($url, '.png') !== false) {
                    $mimeType = 'image/png';
                } else {
                    $mimeType = 'image/png';
                }
            }

            // Créer la réponse avec les en-têtes CORS
            $response = new Response($imageData);
            $response->headers->set('Content-Type', $mimeType);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $response->headers->set('Cache-Control', 'public, max-age=3600');

            return $response;
        } catch (\Exception $e) {
            return new Response('Erreur: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

