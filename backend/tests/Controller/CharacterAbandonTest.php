<?php

namespace App\Tests\Controller;

use App\Entity\Character;
use PHPUnit\Framework\TestCase;

class CharacterAbandonTest extends TestCase
{
    public function testAbandonedCharacterHasExpectedStatusConstant(): void
    {
        $this->assertSame('abandoned', Character::STATUS_ABANDONED);
    }

    public function testAbandonFlowDetachesUser(): void
    {
        $character = new Character();
        $character->setStatus(Character::STATUS_VALIDATED);
        $character->setUser(new \App\Entity\User());

        $character->setStatus(Character::STATUS_ABANDONED);
        $character->setUser(null);

        $this->assertSame(Character::STATUS_ABANDONED, $character->getStatus());
        $this->assertNull($character->getUser());
    }
}
