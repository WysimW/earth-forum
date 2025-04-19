<?php

namespace App\Command;

use App\Repository\FactionRepository;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-faction-thread-relations',
    description: 'Répare les relations bidirectionnelles entre factions et threads',
)]
class FixFactionThreadRelationsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private FactionRepository $factionRepository;
    private ThreadRepository $threadRepository;

    public function __construct(
        EntityManagerInterface $entityManager, 
        FactionRepository $factionRepository,
        ThreadRepository $threadRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->factionRepository = $factionRepository;
        $this->threadRepository = $threadRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Réparation des relations entre factions et threads');

        // Récupérer toutes les factions
        $factions = $this->factionRepository->findAll();
        $io->progressStart(count($factions));

        $fixedRelations = 0;

        // Parcourir chaque faction
        foreach ($factions as $faction) {
            $scenes = $faction->getScenes();
            
            // Vérifier si les threads contiennent également cette faction
            foreach ($scenes as $thread) {
                if (!$thread->getFactions()->contains($faction)) {
                    $thread->addFaction($faction);
                    $fixedRelations++;
                }
            }
            
            $io->progressAdvance();
        }
        
        $io->progressFinish();
        
        // Parcourir tous les threads pour vérifier dans l'autre sens
        $threads = $this->threadRepository->findAll();
        $io->progressStart(count($threads));
        
        foreach ($threads as $thread) {
            $factions = $thread->getFactions();
            
            foreach ($factions as $faction) {
                if (!$faction->getScenes()->contains($thread)) {
                    $faction->addScene($thread);
                    $fixedRelations++;
                }
            }
            
            $io->progressAdvance();
        }
        
        $io->progressFinish();
        
        // Sauvegarder les modifications
        $this->entityManager->flush();
        
        $io->success(sprintf('%d relations ont été réparées.', $fixedRelations));

        return Command::SUCCESS;
    }
} 