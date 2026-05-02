<?php

namespace App\Service;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use RuntimeException;

class S3Service
{
    private S3Client $s3Client;
    private string $bucket;
    private string $region;
    private string $acl;

    public function __construct(
        ParameterBagInterface $params,
        private LoggerInterface $logger
    ) {
        $this->bucket = $_ENV['AWS_S3_BUCKET'] ?? '';
        $this->region = $_ENV['AWS_REGION'] ?? 'eu-north-1';
        $this->acl = $_ENV['AWS_S3_ACL'] ?? 'private';

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $_ENV['AWS_ACCESS_KEY_ID'] ?? '',
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '',
            ],
        ]);
    }

    /**
     * Upload un fichier vers S3
     */
    public function uploadFile(string $filePath, string $s3Key, string $mimeType = null): string
    {
        try {
            $params = [
                'Bucket' => $this->bucket,
                'Key' => $s3Key,
                'SourceFile' => $filePath,
                'ACL' => $this->acl,
            ];

            if ($mimeType) {
                $params['ContentType'] = $mimeType;
            }

            $result = $this->s3Client->putObject($params);

            // Retourner l'URL publique ou pré-signée selon l'ACL
            if ($this->acl === 'public-read') {
                return $result['ObjectURL'];
            } else {
                // Générer une URL pré-signée valide maximum 7 jours (604800 secondes)
                // AWS limite les URLs présignées à 7 jours maximum
                return $this->getPresignedUrl($s3Key, 604800); // 7 jours
            }
        } catch (AwsException $e) {
            $this->logger->error('S3 Upload Error: ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de l\'upload vers S3: ' . $e->getMessage());
        }
    }

    /**
     * Génère une URL pré-signée pour un fichier privé
     * @param string $s3Key La clé S3 du fichier
     * @param int $expiration Durée d'expiration en secondes (maximum 604800 = 7 jours)
     */
    public function getPresignedUrl(string $s3Key, int $expiration = 3600): string
    {
        try {
            // AWS limite les URLs présignées à 7 jours maximum (604800 secondes)
            if ($expiration > 604800) {
                $expiration = 604800;
                $this->logger->warning("L'expiration a été limitée à 7 jours (604800 secondes) pour respecter les limites AWS");
            }

            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $s3Key,
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, '+' . $expiration . ' seconds');
            return (string) $request->getUri();
        } catch (AwsException $e) {
            $this->logger->error('S3 Presigned URL Error: ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de la génération de l\'URL pré-signée: ' . $e->getMessage());
        }
    }

    /**
     * Supprime un fichier de S3
     */
    public function deleteFile(string $s3Key): bool
    {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $s3Key,
            ]);
            return true;
        } catch (AwsException $e) {
            $this->logger->error('S3 Delete Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Liste les fichiers dans un préfixe
     */
    public function listFiles(string $prefix = ''): array
    {
        try {
            $result = $this->s3Client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);

            return $result['Contents'] ?? [];
        } catch (AwsException $e) {
            $this->logger->error('S3 List Error: ' . $e->getMessage());
            return [];
        }
    }
}

