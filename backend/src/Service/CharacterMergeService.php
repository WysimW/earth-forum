<?php

namespace App\Service;

use App\Entity\Character;
use App\Entity\CharacterRelation;
use App\Entity\FactionCharacterApplication;
use App\Entity\FactionCharacterMembership;
use App\Entity\Messaging\Message;
use App\Entity\Post;
use App\Entity\RpActivityRegistration;
use App\Entity\Thread;
use App\Repository\CharacterRelationRepository;
use App\Repository\FactionCharacterApplicationRepository;
use App\Repository\FactionCharacterMembershipRepository;
use App\Repository\PostRepository;
use App\Repository\RpActivityRegistrationRepository;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;

class CharacterMergeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PostRepository $postRepository,
        private ThreadRepository $threadRepository,
        private CharacterRelationRepository $characterRelationRepository,
        private FactionCharacterMembershipRepository $factionCharacterMembershipRepository,
        private FactionCharacterApplicationRepository $factionCharacterApplicationRepository,
        private RpActivityRegistrationRepository $rpActivityRegistrationRepository,
    ) {
    }

    /**
     * @return array{posts: int, threadsAsCreator: int, threadsAsSheet: int, threadParticipants: int, relations: int, factionMemberships: int, factionApplications: int, rpRegistrations: int, messages: int, factions: int}
     */
    public function merge(Character $survivor, Character $absorbed): array
    {
        $this->validateMerge($survivor, $absorbed);

        $counts = [
            'posts' => 0,
            'threadsAsCreator' => 0,
            'threadsAsSheet' => 0,
            'threadParticipants' => 0,
            'relations' => 0,
            'factionMemberships' => 0,
            'factionApplications' => 0,
            'rpRegistrations' => 0,
            'messages' => 0,
            'factions' => 0,
        ];

        foreach ($this->postRepository->findBy(['character' => $absorbed]) as $post) {
            if ($post instanceof Post) {
                $post->setCharacter($survivor);
                ++$counts['posts'];
            }
        }

        foreach ($this->threadRepository->findBy(['characterCreator' => $absorbed]) as $thread) {
            if ($thread instanceof Thread) {
                $thread->setCharacterCreator($survivor);
                ++$counts['threadsAsCreator'];
            }
        }

        foreach ($this->threadRepository->findBy(['characterSheet' => $absorbed]) as $thread) {
            if ($thread instanceof Thread) {
                $thread->setCharacterSheet($survivor);
                ++$counts['threadsAsSheet'];
            }
        }

        $participantThreads = $this->threadRepository->createQueryBuilder('t')
            ->innerJoin('t.participants', 'p')
            ->where('p = :absorbed')
            ->setParameter('absorbed', $absorbed)
            ->getQuery()
            ->getResult();

        foreach ($participantThreads as $thread) {
            if (!$thread instanceof Thread) {
                continue;
            }
            $thread->removeParticipant($absorbed);
            if (!$thread->getParticipants()->contains($survivor)) {
                $thread->addParticipant($survivor);
            }
            ++$counts['threadParticipants'];
        }

        foreach ($this->characterRelationRepository->findCharacterRelations($absorbed) as $relation) {
            if (!$relation instanceof CharacterRelation) {
                continue;
            }

            if ($relation->getSourceCharacter() === $absorbed) {
                if ($relation->getTargetCharacter()?->getId() === $survivor->getId()) {
                    $this->entityManager->remove($relation);
                } else {
                    $relation->setSourceCharacter($survivor);
                }
            }

            if ($relation->getTargetCharacter() === $absorbed) {
                if ($relation->getSourceCharacter()?->getId() === $survivor->getId()) {
                    $this->entityManager->remove($relation);
                } else {
                    $relation->setTargetCharacter($survivor);
                }
            }

            ++$counts['relations'];
        }

        foreach ($this->factionCharacterMembershipRepository->findBy(['character' => $absorbed]) as $membership) {
            if (!$membership instanceof FactionCharacterMembership) {
                continue;
            }

            $faction = $membership->getFaction();
            $existing = $this->factionCharacterMembershipRepository->findOneBy([
                'character' => $survivor,
                'faction' => $faction,
            ]);

            if ($existing) {
                $this->entityManager->remove($membership);
            } else {
                $membership->setCharacter($survivor);
            }
            ++$counts['factionMemberships'];
        }

        foreach ($this->factionCharacterApplicationRepository->findBy(['character' => $absorbed]) as $application) {
            if (!$application instanceof FactionCharacterApplication) {
                continue;
            }

            $faction = $application->getFaction();
            $existing = $this->factionCharacterApplicationRepository->findOneBy([
                'character' => $survivor,
                'faction' => $faction,
            ]);

            if ($existing) {
                $this->entityManager->remove($application);
            } else {
                $application->setCharacter($survivor);
            }
            ++$counts['factionApplications'];
        }

        foreach ($this->rpActivityRegistrationRepository->findBy(['character' => $absorbed]) as $registration) {
            if (!$registration instanceof RpActivityRegistration) {
                continue;
            }

            $activity = $registration->getActivity();
            $existing = $this->rpActivityRegistrationRepository->findOneBy([
                'character' => $survivor,
                'activity' => $activity,
            ]);

            if ($existing) {
                $this->entityManager->remove($registration);
            } else {
                $registration->setCharacter($survivor);
            }
            ++$counts['rpRegistrations'];
        }

        $messages = $this->entityManager->getRepository(Message::class)->findBy(['character' => $absorbed]);
        foreach ($messages as $message) {
            if ($message instanceof Message) {
                $message->setCharacter($survivor);
                ++$counts['messages'];
            }
        }

        foreach ($absorbed->getFactionsRelation()->toArray() as $faction) {
            if (!$survivor->getFactionsRelation()->contains($faction)) {
                $survivor->addFactionRelation($faction);
                ++$counts['factions'];
            }
        }

        $this->entityManager->remove($absorbed);
        $this->entityManager->flush();

        return $counts;
    }

    private function validateMerge(Character $survivor, Character $absorbed): void
    {
        if ($survivor === $absorbed) {
            throw new \InvalidArgumentException('Impossible de fusionner un personnage avec lui-même.');
        }

        if ($survivor->getId() !== null && $absorbed->getId() !== null && $survivor->getId() === $absorbed->getId()) {
            throw new \InvalidArgumentException('Impossible de fusionner un personnage avec lui-même.');
        }

        if ($survivor->isEventCharacter() || $absorbed->isEventCharacter()) {
            throw new \InvalidArgumentException('Les personnages event ne peuvent pas être fusionnés.');
        }

        $survivorUniverseId = $survivor->getUniverse()?->getId();
        $absorbedUniverseId = $absorbed->getUniverse()?->getId();

        if (!$survivorUniverseId || !$absorbedUniverseId || $survivorUniverseId !== $absorbedUniverseId) {
            throw new \InvalidArgumentException('Les deux personnages doivent appartenir au même univers.');
        }
    }
}
