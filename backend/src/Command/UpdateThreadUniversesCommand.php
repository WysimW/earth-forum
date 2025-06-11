<?php

namespace App\Command;

use App\Entity\Thread;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-thread-universes',
    description: 'Met à jour l\'univers des threads en fonction de l\'univers de leur forum',
)]
class UpdateThreadUniversesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Mise à jour des univers des threads');

        // Récupérer tous les threads dont l'univers est null
        $threads = $this->entityManager->getRepository(Thread::class)->findBy(['universe' => null]);
        $count = count($threads);

        if ($count === 0) {
            $io->success('Aucun thread à mettre à jour.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Nombre de threads à mettre à jour : %d', $count));
        $updated = 0;

        foreach ($threads as $thread) {
            $forum = $thread->getForum();
            if ($forum && $forum->getUniverse()) {
                $thread->setUniverse($forum->getUniverse());
                $updated++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Mise à jour terminée. %d threads mis à jour sur %d.',
            $updated,
            $count
        ));

        return Command::SUCCESS;
    }
} 