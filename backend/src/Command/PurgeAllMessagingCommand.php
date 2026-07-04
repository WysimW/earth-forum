<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Messaging\Conversation;
use App\Entity\Messaging\ConversationParticipant;
use App\Entity\Messaging\Message;
use App\Entity\Messaging\MessageReport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:messaging:purge-all',
    description: 'Supprime toutes les conversations, participants, messages et signalements de messagerie (tous les utilisateurs).',
)]
final class PurgeAllMessagingCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Exécuter sans demander de confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force')) {
            if (!$io->confirm('Supprimer toute la messagerie (conversations, messages, signalements) pour tous les comptes ?', false)) {
                $io->warning('Annulé.');

                return Command::SUCCESS;
            }
        }

        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        try {
            $this->entityManager->createQuery(
                sprintf('DELETE FROM %s', MessageReport::class)
            )->execute();

            $this->entityManager->createQuery(
                sprintf('UPDATE %s m SET m.replyTo = NULL', Message::class)
            )->execute();

            $this->entityManager->createQuery(
                sprintf('DELETE FROM %s', Message::class)
            )->execute();

            $this->entityManager->createQuery(
                sprintf('DELETE FROM %s', ConversationParticipant::class)
            )->execute();

            $this->entityManager->createQuery(
                sprintf('DELETE FROM %s', Conversation::class)
            )->execute();

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        $io->success('Messagerie entièrement vidée en base.');

        return Command::SUCCESS;
    }
}
