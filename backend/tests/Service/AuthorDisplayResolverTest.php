<?php

namespace App\Tests\Service;

use App\Entity\Character;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use App\Service\AuthorDisplayResolver;
use PHPUnit\Framework\TestCase;

class AuthorDisplayResolverTest extends TestCase
{
    private AuthorDisplayResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new AuthorDisplayResolver();
    }

    public function testResolvePostAuthorHrpWithoutUserReturnsAnonymous(): void
    {
        $post = new Post();
        $post->setAuthor(null);

        $resolved = $this->resolver->resolvePostAuthor($post, false);

        $this->assertSame('Anonyme', $resolved['displayName']);
        $this->assertTrue($resolved['isAnonymous']);
        $this->assertNull($resolved['userId']);
    }

    public function testResolvePostAuthorRpWithCharacterWithoutUserUsesCharacterName(): void
    {
        $character = new Character();
        $character->setName('Bruce Wayne');

        $post = new Post();
        $post->setAuthor(null);
        $post->setCharacter($character);

        $resolved = $this->resolver->resolvePostAuthor($post, true);

        $this->assertSame('Bruce Wayne', $resolved['displayName']);
        $this->assertSame('Bruce Wayne', $resolved['characterName']);
        $this->assertNull($resolved['userId']);
    }

    public function testResolvePostAuthorRpWithoutUserNorCharacterReturnsAnonymous(): void
    {
        $post = new Post();
        $post->setAuthor(null);

        $resolved = $this->resolver->resolvePostAuthor($post, true);

        $this->assertSame('Anonyme', $resolved['displayName']);
        $this->assertTrue($resolved['isAnonymous']);
    }

    public function testResolveThreadAuthorRpUsesCharacterCreatorWhenNoUser(): void
    {
        $character = new Character();
        $character->setName('Clark Kent');

        $thread = new Thread();
        $thread->setType('roleplay');
        $thread->setAuthor(null);
        $thread->setCharacterCreator($character);

        $resolved = $this->resolver->resolveThreadAuthor($thread);

        $this->assertSame('Clark Kent', $resolved['displayName']);
        $this->assertNull($resolved['userId']);
    }

    public function testResolveThreadAuthorHrpWithoutUserReturnsAnonymous(): void
    {
        $thread = new Thread();
        $thread->setType('normal');
        $thread->setAuthor(null);

        $resolved = $this->resolver->resolveThreadAuthor($thread);

        $this->assertSame('Anonyme', $resolved['displayName']);
        $this->assertTrue($resolved['isAnonymous']);
    }

    public function testResolveThreadAuthorWithUserReturnsPseudo(): void
    {
        $user = new User();
        $user->setPseudo('player_one');

        $thread = new Thread();
        $thread->setType('normal');
        $thread->setAuthor($user);

        $resolved = $this->resolver->resolveThreadAuthor($thread);

        $this->assertSame('player_one', $resolved['displayName']);
        $this->assertFalse($resolved['isAnonymous']);
    }
}
