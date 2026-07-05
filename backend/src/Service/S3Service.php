<?php

namespace App\Service;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class S3Service
{
    private S3Client $s3Client;
    private string $bucket;
    private string $region;
    private string $acl;

    public function __construct(
        private LoggerInterface $logger
    ) {
        $this->bucket = $_ENV['AWS_S3_BUCKET'] ?? '';
        $this->region = $_ENV['AWS_REGION'] ?? 'eu-west-3';
        $acl = $_ENV['AWS_S3_ACL'] ?? 'private';
        $this->acl = $acl !== '' ? $acl : 'private';

        $config = [
            'version' => 'latest',
            'region' => $this->region,
        ];

        $accessKey = trim((string) ($_ENV['AWS_ACCESS_KEY_ID'] ?? ''));
        $secretKey = trim((string) ($_ENV['AWS_SECRET_ACCESS_KEY'] ?? ''));
        // Sans clés explicites : chaîne par défaut AWS (rôle EC2, ~/.aws/credentials en dev)
        if ($accessKey !== '' && $secretKey !== '') {
            $config['credentials'] = [
                'key' => $accessKey,
                'secret' => $secretKey,
            ];
        }

        $this->s3Client = new S3Client($config);
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
     * Télécharge un objet S3 (pour le proxy media public).
     *
     * @return array{body: string, contentType: string}
     */
    public function getObjectContent(string $s3Key): array
    {
        try {
            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $s3Key,
            ]);

            return [
                'body' => (string) $result['Body'],
                'contentType' => $result['ContentType'] ?? 'application/octet-stream',
            ];
        } catch (AwsException $e) {
            $this->logger->error('S3 GetObject Error: ' . $e->getMessage());
            throw new RuntimeException('Impossible de lire le fichier S3: ' . $e->getMessage(), 0, $e);
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

