<?php

namespace App\Command;

use App\Service\ForumActif\ForumActifExporter;
use App\Service\ForumActif\ForumActifImporter;
use App\Service\ForumActif\ForumActifState;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:forumactif:sync',
    description: 'Exporte puis importe les threads ForumActif année par année (ex. --year=2026)',
)]
final class ImportForumactifCommand extends Command
{
    public function __construct(
        private readonly ForumActifExporter $exporter,
        private readonly ForumActifImporter $importer,
        private readonly string $outputDir,
        private readonly string $stateFile,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('year', 'y', InputOption::VALUE_REQUIRED, 'Année à traiter (ex. 2026)')
            ->addOption('export-only', null, InputOption::VALUE_NONE, 'Exporte vers JSON sans importer en base')
            ->addOption('import-only', null, InputOption::VALUE_NONE, 'Importe depuis un export JSON existant')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule l\'import sans écrire en base')
            ->addOption('resume', null, InputOption::VALUE_NONE, 'Reprend un export interrompu (ignore les sujets déjà exportés)')
            ->addOption('forum-id', null, InputOption::VALUE_REQUIRED, 'Limiter l\'export à un forum source ForumActif (ex. 66 pour Asile d\'Arkham)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $year = (int) $input->getOption('year');
        if ($year < 2000 || $year > 2100) {
            $io->error('Option --year obligatoire et valide (ex. --year=2026).');

            return Command::FAILURE;
        }

        $exportOnly = (bool) $input->getOption('export-only');
        $importOnly = (bool) $input->getOption('import-only');
        if ($exportOnly && $importOnly) {
            $io->error('Les options --export-only et --import-only sont incompatibles.');

            return Command::FAILURE;
        }

        $state = ForumActifState::load($this->stateFile);
        $forumSourceId = $input->getOption('forum-id');
        $forumSourceId = $forumSourceId !== null ? (int) $forumSourceId : null;

        $io->title(sprintf('ForumActif → Earth Forum (%d)', $year));

        if (!$importOnly) {
            $io->section('Export');
            $exportStats = $this->exporter->exportYear(
                $year,
                $this->outputDir,
                $state,
                $io,
                (bool) $input->getOption('resume'),
                $forumSourceId,
            );

            $io->success(sprintf(
                'Export terminé : %d sujets, %d messages (%d sujets ignorés en reprise).',
                $exportStats['threads'],
                $exportStats['posts'],
                $exportStats['skippedThreads'],
            ));
        }

        if (!$exportOnly) {
            $io->section('Import');
            $importStats = $this->importer->importYear(
                $year,
                $this->outputDir,
                $state,
                $io,
                (bool) $input->getOption('dry-run'),
            );

            $io->success(sprintf(
                'Import terminé : %d sujets créés, %d messages importés, %d ignorés.',
                $importStats['threads'],
                $importStats['posts'],
                $importStats['skipped'],
            ));

            if ($importStats['unmappedForums'] !== []) {
                $io->warning([
                    'Forums source sans correspondance earth-forum :',
                    ...array_map(static fn (string $slug): string => '  - ' . $slug, $importStats['unmappedForums']),
                    'Ajoutez-les dans backend/config/forumactif/forum-mapping.json',
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
