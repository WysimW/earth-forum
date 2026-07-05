<?php

namespace App\Tests\Service;

use App\Entity\Character;
use App\Entity\Univers;
use App\Repository\CharacterRelationRepository;
use App\Repository\FactionCharacterApplicationRepository;
use App\Repository\FactionCharacterMembershipRepository;
use App\Repository\PostRepository;
use App\Repository\RpActivityRegistrationRepository;
use App\Repository\ThreadRepository;
use App\Service\CharacterMergeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CharacterMergeServiceTest extends TestCase
{
    public function testMergeRejectsDifferentUniverses(): void
    {
        $service = new CharacterMergeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(PostRepository::class),
            $this->createMock(ThreadRepository::class),
            $this->createMock(CharacterRelationRepository::class),
            $this->createMock(FactionCharacterMembershipRepository::class),
            $this->createMock(FactionCharacterApplicationRepository::class),
            $this->createMock(RpActivityRegistrationRepository::class),
        );

        $universeA = new Univers();
        $universeB = new Univers();

        $survivor = new Character();
        $survivor->setName('Survivor');
        $survivor->setUniverse($universeA);

        $absorbed = new Character();
        $absorbed->setName('Absorbed');
        $absorbed->setUniverse($universeB);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('même univers');

        $service->merge($survivor, $absorbed);
    }

    public function testMergeRejectsSameCharacter(): void
    {
        $service = new CharacterMergeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(PostRepository::class),
            $this->createMock(ThreadRepository::class),
            $this->createMock(CharacterRelationRepository::class),
            $this->createMock(FactionCharacterMembershipRepository::class),
            $this->createMock(FactionCharacterApplicationRepository::class),
            $this->createMock(RpActivityRegistrationRepository::class),
        );

        $character = new Character();
        $character->setName('Same');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lui-même');

        $service->merge($character, $character);
    }

    public function testMergeRejectsEventCharacters(): void
    {
        $service = new CharacterMergeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(PostRepository::class),
            $this->createMock(ThreadRepository::class),
            $this->createMock(CharacterRelationRepository::class),
            $this->createMock(FactionCharacterMembershipRepository::class),
            $this->createMock(FactionCharacterApplicationRepository::class),
            $this->createMock(RpActivityRegistrationRepository::class),
        );

        $universe = new Univers();

        $survivor = new Character();
        $survivor->setName('Survivor');
        $survivor->setUniverse($universe);
        $survivor->setKind(Character::KIND_EVENT);

        $absorbed = new Character();
        $absorbed->setName('Absorbed');
        $absorbed->setUniverse($universe);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('event');

        $service->merge($survivor, $absorbed);
    }
}
