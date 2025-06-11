<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AvatarService
{
    private string $targetDirectory;
    private SluggerInterface $slugger;
    private ImageManager $imageManager;
    private int $maxFileSize = 5 * 1024 * 1024; // 5MB
    private array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(string $avatarDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $avatarDirectory;
        $this->slugger = $slugger;
        $this->imageManager = new ImageManager(new Driver());
    }

    public function upload(UploadedFile $file, string $characterName): array
    {
        // Les validations sont faites dans le contrôleur
        // Ici on ne fait que le traitement du fichier

        // Nettoyer le nom de fichier original
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->guessExtension();
        
        // Créer un nom simple et propre
        $safeCharacterName = $this->slugger->slug($characterName);
        $fileName = 'original_' . $safeCharacterName . '-' . uniqid() . '.' . $extension;

        try {
            $file->move($this->targetDirectory, $fileName);
            
            // Redimensionner l'image pour optimiser le stockage
            $this->optimizeImage($this->targetDirectory . '/' . $fileName);
            
            return [
                'filename' => $fileName,
                'originalName' => $file->getClientOriginalName(),
                'size' => filesize($this->targetDirectory . '/' . $fileName), // Lire la taille du fichier sauvegardé
                'path' => '/uploads/avatars/' . $fileName
            ];
        } catch (FileException $e) {
            throw new \RuntimeException('Erreur lors de l\'upload du fichier');
        }
    }

    public function delete(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $filePath = $this->targetDirectory . '/' . $filename;
        
        // Supprimer le fichier principal
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function deleteAllVersions(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        // Supprimer le fichier principal
        $this->delete($filename);
        
        // Déterminer le nom de base pour les versions dérivées
        // Si le fichier commence par 'original_', on utilise le nom complet
        // Sinon, on génère les noms avec les préfixes
        if (str_starts_with($filename, 'original_')) {
            $baseFilename = $filename;
        } else {
            $baseFilename = 'original_' . $filename;
        }
        
        // Supprimer les versions dérivées si elles existent
        $portraitFilename = str_replace('original_', 'portrait_', $baseFilename);
        $circleFilename = str_replace('original_', 'circle_', $baseFilename);
        
        $this->delete($portraitFilename);
        $this->delete($circleFilename);
    }

    public function cropImage(string $filename, array $cropData): array
    {
        $imagePath = $this->targetDirectory . '/' . $filename;
        
        if (!file_exists($imagePath)) {
            throw new \InvalidArgumentException('Image non trouvée');
        }

        try {
            $image = $this->imageManager->read($imagePath);
            
            // Générer les versions recadrées
            $versions = [];
            
            // Données de recadrage
            $x = (int)($cropData['x'] ?? 0);
            $y = (int)($cropData['y'] ?? 0);
            $width = (int)($cropData['width'] ?? 200);
            $height = (int)($cropData['height'] ?? 200);
            
            // Version portrait (ratio 1.2:1)
            $portraitWidth = $width;
            $portraitHeight = (int)($portraitWidth * 1.2);
            
            $portraitImage = clone $image;
            $portraitImage->crop($portraitWidth, $portraitHeight, $x, $y);
            
            $portraitFilename = 'portrait_' . $filename;
            $portraitImage->save($this->targetDirectory . '/' . $portraitFilename);
            $versions['portrait'] = $portraitFilename;
            
            // Version circulaire (carré)
            $circleSize = min($portraitWidth, $portraitHeight);
            $circleImage = clone $image;
            $circleImage->crop($circleSize, $circleSize, $x, $y);
            
            $circleFilename = 'circle_' . $filename;
            $circleImage->save($this->targetDirectory . '/' . $circleFilename);
            $versions['circle'] = $circleFilename;
            
            return $versions;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors du recadrage de l\'image : ' . $e->getMessage());
        }
    }

    private function optimizeImage(string $imagePath): void
    {
        try {
            $image = $this->imageManager->read($imagePath);
            
            // Redimensionner si l'image est trop grande
            if ($image->width() > 1000 || $image->height() > 1000) {
                $image->scale(width: 1000, height: 1000);
            }
            
            // Sauvegarder avec compression
            $image->save($imagePath, quality: 85);
            
        } catch (\Exception $e) {
            // Si l'optimisation échoue, on garde l'image originale
        }
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
} 