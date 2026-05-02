<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\Table(name: 'media')]
#[ApiResource(
    operations: [],
    routePrefix: '/admin/media'
)]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['media_list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['media_list'])]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    #[Groups(['media_list'])]
    private ?string $originalFilename = null;

    #[ORM\Column(length: 255)]
    #[Groups(['media_list'])]
    private ?string $mimeType = null;

    #[ORM\Column]
    #[Groups(['media_list'])]
    private ?int $size = null;

    #[ORM\Column(length: 500)]
    #[Groups(['media_list'])]
    private ?string $url = null;

    #[ORM\Column(length: 500)]
    private ?string $s3Key = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['media_list'])]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadedBy = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['media_list'])]
    private ?string $type = null; // 'image', 'document', etc.

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['media_list'])]
    private ?Media $parentMedia = null; // Image source pour les versions croppées

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getS3Key(): ?string
    {
        return $this->s3Key;
    }

    public function setS3Key(string $s3Key): static
    {
        $this->s3Key = $s3Key;
        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getParentMedia(): ?Media
    {
        return $this->parentMedia;
    }

    public function setParentMedia(?Media $parentMedia): static
    {
        $this->parentMedia = $parentMedia;
        return $this;
    }

    /**
     * Vérifie si l'URL pré-signée est expirée
     * Les URLs pré-signées S3 contiennent un paramètre X-Amz-Expires et X-Amz-Date
     */
    public function isUrlExpired(): bool
    {
        if (!$this->url) {
            return true;
        }

        // Si l'URL ne contient pas de paramètres de signature, elle n'est pas pré-signée
        if (strpos($this->url, 'X-Amz-Signature') === false) {
            return false; // URL publique, jamais expirée
        }

        // Extraire les paramètres de l'URL
        $parsedUrl = parse_url($this->url);
        if (!isset($parsedUrl['query'])) {
            return false;
        }

        parse_str($parsedUrl['query'], $params);

        // Vérifier si les paramètres nécessaires existent
        if (!isset($params['X-Amz-Date']) || !isset($params['X-Amz-Expires'])) {
            return false;
        }

        // Parser la date de l'URL (format: YYYYMMDDTHHMMSSZ)
        $dateStr = $params['X-Amz-Date'];
        $expires = (int) $params['X-Amz-Expires'];

        try {
            $dateTime = \DateTimeImmutable::createFromFormat('Ymd\THis\Z', $dateStr);
            if (!$dateTime) {
                return true; // Si on ne peut pas parser, considérer comme expirée
            }

            // Calculer la date d'expiration
            $expirationDate = $dateTime->modify("+{$expires} seconds");
            $now = new \DateTimeImmutable();

            return $now > $expirationDate;
        } catch (\Exception $e) {
            return true; // En cas d'erreur, considérer comme expirée
        }
    }
}

