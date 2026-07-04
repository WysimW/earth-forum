<?php

declare(strict_types=1);

namespace App\Service\Messaging;

use App\Entity\Messaging\Message;
use App\Entity\User;
use App\Repository\Messaging\MessageReportRepository;
use App\Service\S3MediaUrlResolver;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class MessagingJsonSerializer
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly MessageReportRepository $messageReportRepository,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    private function defaultAvatar(): string
    {
        return $this->parameterBag->has('app.default_avatar')
            ? (string) $this->parameterBag->get('app.default_avatar')
            : '';
    }

    private function defaultCharacterAvatar(): string
    {
        return $this->parameterBag->has('app.default_character_avatar')
            ? (string) $this->parameterBag->get('app.default_character_avatar')
            : '';
    }

    /**
     * @param Message[] $messages
     * @return array<int, array<string, mixed>>
     */
    public function serializeMessagesForViewer(array $messages, User $viewer): array
    {
        $ids = [];
        foreach ($messages as $message) {
            $id = $message->getId();
            if (null !== $id) {
                $ids[] = $id;
            }
        }
        $reportedList = $this->messageReportRepository->findMessageIdsReportedByUser($viewer, $ids);
        $reportedSet = array_fill_keys($reportedList, true);

        $out = [];
        foreach ($messages as $message) {
            $mid = $message->getId();
            $hasReported = null !== $mid && isset($reportedSet[$mid]);
            $out[] = $this->serializeMessage($message, $viewer, $hasReported);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(Message $message, User $viewer, bool $viewerHasReportedThisMessage = false): array
    {
        $author = $message->getAuthor();
        if (!$author instanceof User) {
            throw new \LogicException('Message sans auteur');
        }

        $canEdit = !$message->isDeleted() && $author === $viewer;
        $canDelete = $canEdit;
        $canReport = !$message->isDeleted() && $author !== $viewer && !$viewerHasReportedThisMessage;

        $data = [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $message->getUpdatedAt() ? $message->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            'isEdited' => $message->isEdited(),
            'isDeleted' => $message->isDeleted(),
            'userSelfDeleted' => $message->isUserSelfDeletedContent(),
            'moderationDeleted' => $message->isModeratedDeletedContent(),
            'isRoleplay' => $message->isRoleplay(),
            'isSentByCurrentUser' => $author === $viewer,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'canReport' => $canReport,
            'hasReportedByViewer' => $viewerHasReportedThisMessage,
            'author' => [
                'id' => $author->getId(),
                'username' => $author->getUsername(),
                'avatar' => ($u = $this->s3MediaUrlResolver->resolve($author->getAvatar())) !== null && $u !== ''
                    ? $u
                    : $this->defaultAvatar(),
            ],
        ];

        if ($message->isRoleplay() && $message->getCharacter()) {
            $character = $message->getCharacter();
            $data['character'] = [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'avatar' => ($cAv = $this->s3MediaUrlResolver->resolve($character->getAvatar())) !== null && $cAv !== ''
                    ? $cAv
                    : $this->defaultCharacterAvatar(),
            ];
        }

        return $data;
    }

    public function userAvatar(User $user): string
    {
        $u = $this->s3MediaUrlResolver->resolve($user->getAvatar());

        return ($u !== null && $u !== '') ? $u : $this->defaultAvatar();
    }
}
