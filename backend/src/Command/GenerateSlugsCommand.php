<?php

namespace App\Command;

use App\Entity\Forum;
use App\Entity\Thread;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateSlugsCommand extends Command
{
    protected static $defaultName = 'app:generate-slugs';
    protected static $defaultDescription = 'Génère les slugs pour les forums et threads existants';

    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:generate-slugs')
            ->setDescription('Génère les slugs pour les forums et threads existants')
            ->setHelp('Cette commande permet de générer les slugs pour tous les forums et threads existants qui n\'en ont pas encore.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Générer les slugs pour les forums
        $forums = $this->entityManager->getRepository(Forum::class)->findAll();
        $io->progressStart(count($forums));
        
        foreach ($forums as $forum) {
            $baseSlug = $this->slugger->slug($forum->getName())->lower();
            $slug = $baseSlug;
            $counter = 1;
            
            // S'assurer que le slug est unique
            while ($this->slugExists($slug, Forum::class, $forum->getId())) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $forum->setSlug($slug);
            $io->progressAdvance();
        }
        
        // Générer les slugs pour les threads
        $threads = $this->entityManager->getRepository(Thread::class)->findAll();
        $io->progressStart(count($threads));
        
        foreach ($threads as $thread) {
            $baseSlug = $this->slugger->slug($thread->getTitle())->lower();
            $slug = $baseSlug;
            $counter = 1;
            
            // S'assurer que le slug est unique
            while ($this->slugExists($slug, Thread::class, $thread->getId())) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $thread->setSlug($slug);
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        
        $io->success('Les slugs ont été générés avec succès !');
        
        return Command::SUCCESS;
    }

    private function slugExists(string $slug, string $entityClass, ?int $excludeId = null): bool
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from($entityClass, 'e')
            ->where('e.slug = :slug')
            ->setParameter('slug', $slug);
        
        if ($excludeId !== null) {
            $qb->andWhere('e.id != :id')
               ->setParameter('id', $excludeId);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}