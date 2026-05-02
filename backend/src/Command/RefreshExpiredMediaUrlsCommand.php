<?php

namespace App\Command;

use App\Entity\Media;
use App\Repository\MediaRepository;
use App\Service\S3Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RefreshExpiredMediaUrlsCommand extends Command
{
    protected static $defaultName = 'app:refresh-expired-media-urls';
    protected static $defaultDescription = 'Régénère les URLs pré-signées expirées pour tous les médias';

    private EntityManagerInterface $entityManager;
    private MediaRepository $mediaRepository;
    private S3Service $s3Service;

    public function __construct(
        EntityManagerInterface $entityManager,
        MediaRepository $mediaRepository,
        S3Service $s3Service
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->mediaRepository = $mediaRepository;
        $this->s3Service = $s3Service;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:refresh-expired-media-urls')
            ->setDescription('Régénère les URLs pré-signées expirées pour tous les médias')
            ->setHelp('Cette commande régénère automatiquement toutes les URLs pré-signées S3 qui ont expiré.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Régénération des URLs expirées');
        
        // Récupérer tous les médias
        $allMedia = $this->mediaRepository->findAll();
        $io->info(sprintf('Analyse de %d médias...', count($allMedia)));
        
        $refreshedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $errors = [];
        
        $io->progressStart(count($allMedia));
        
        foreach ($allMedia as $media) {
            if ($media->isUrlExpired() && $media->getS3Key()) {
                try {
                    $newUrl = $this->s3Service->getPresignedUrl($media->getS3Key(), 604800); // 7 jours
                    $media->setUrl($newUrl);
                    $this->entityManager->persist($media);
                    $refreshedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'id' => $media->getId(),
                        'filename' => $media->getFilename(),
                        'error' => $e->getMessage()
                    ];
                    $io->warning(sprintf('Erreur pour le média ID %d: %s', $media->getId(), $e->getMessage()));
                }
            } else {
                $skippedCount++;
            }
            
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        
        // Afficher le résumé
        $io->section('Résumé');
        $io->table(
            ['Statut', 'Nombre'],
            [
                ['URLs régénérées', $refreshedCount],
                ['URLs non expirées (ignorées)', $skippedCount],
                ['Erreurs', $errorCount],
                ['Total', count($allMedia)]
            ]
        );
        
        if (!empty($errors)) {
            $io->warning(sprintf('%d erreur(s) rencontrée(s)', count($errors)));
            foreach ($errors as $error) {
                $io->text(sprintf('  - Média ID %d (%s): %s', $error['id'], $error['filename'], $error['error']));
            }
        }
        
        if ($refreshedCount > 0) {
            $io->success(sprintf('%d URL(s) régénérée(s) avec succès !', $refreshedCount));
        } else {
            $io->info('Aucune URL expirée trouvée.');
        }
        
        return Command::SUCCESS;
    }
}
