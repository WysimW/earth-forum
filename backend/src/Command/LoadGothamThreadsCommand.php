<?php

namespace App\Command;

use App\DataFixtures\GothamCityThreadsFixtures;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:load-gotham-threads',
    description: 'Charge les fixtures de threads pour Gotham City',
)]
class LoadGothamThreadsCommand extends Command
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Chargement des threads pour Gotham City...');
        
        $fixture = new GothamCityThreadsFixtures();
        
        $manager = $this->doctrine->getManager();
        
        try {
            $fixture->load($manager);
            $output->writeln('<info>✓ Fixtures chargées avec succès !</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>✗ Erreur : ' . $e->getMessage() . '</error>');
            if ($output->isVerbose()) {
                $output->writeln('<error>Trace : ' . $e->getTraceAsString() . '</error>');
            }
            return Command::FAILURE;
        }
    }
}

