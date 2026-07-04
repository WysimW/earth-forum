<?php

namespace App\Command;

use App\Repository\RpActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rp-activities:send-reminders',
    description: 'Déclenche les relances automatiques des events/missions RP'
)]
class SendRpActivityRemindersCommand extends Command
{
    public function __construct(
        private readonly RpActivityRepository $rpActivityRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $dueActivities = $this->rpActivityRepository->findDueReminders($now);

        if ($dueActivities === []) {
            $io->success('Aucune relance à envoyer.');
            return Command::SUCCESS;
        }

        $io->title('Relances RP automatiques');
        $io->progressStart(count($dueActivities));

        foreach ($dueActivities as $activity) {
            $registeredCharacterIds = [];
            foreach ($activity->getRegistrations() as $registration) {
                if (!$registration->isRegistered()) {
                    continue;
                }
                $registeredCharacterIds[] = $registration->getCharacter()?->getId();
            }

            // Notification fallback : log structuré si aucun canal dédié n'est branché.
            $this->logger->info('rp_activity_reminder_dispatched', [
                'activity_id' => $activity->getId(),
                'kind' => $activity->getKind(),
                'title' => $activity->getTitle(),
                'universe_slug' => $activity->getUniverse()?->getSlug(),
                'faction_id' => $activity->getFaction()?->getId(),
                'registered_character_ids' => array_values(array_filter($registeredCharacterIds)),
                'linked_thread_ids' => array_map(
                    static fn ($thread) => $thread->getId(),
                    $activity->getThreads()->toArray()
                ),
            ]);

            $activity->setReminderSentAt($now);
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();
        $io->success(sprintf('%d relance(s) envoyée(s).', count($dueActivities)));

        return Command::SUCCESS;
    }
}
