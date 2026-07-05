<?php

namespace App\Command;

use App\Service\DcEarth\DcEarthForumImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:dcearth:import-forums',
    description: 'Importe la structure des forums DC-Earth depuis share/forums-dcearth.json (sans threads ni messages)',
)]
final class ImportDcEarthForumsCommand extends Command
{
    public function __construct(
        private readonly DcEarthForumImporter $importer,
        private readonly string $defaultSourceFile,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Chemin vers le JSON source (défaut : share/forums-dcearth.json)',
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule l\'import sans écrire en base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sourceFile = (string) ($input->getOption('file') ?: $this->defaultSourceFile);
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('Import structure forums DC-Earth');

        if ($dryRun) {
            $io->note('Mode dry-run : aucune écriture en base.');
        }

        try {
            $stats = $this->importer->import($sourceFile, $io, $dryRun);
        } catch (\RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Import terminé : %d créés, %d mis à jour, %d ignorés (%d sections source).',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
            $stats['categories'],
        ));

        return Command::SUCCESS;
    }
}
