<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'email' => 'admin@dce.com',
                'roles' => ['ROLE_ADMIN'],
                'pseudo' => 'DcAdmin',
                'avatar' => 'https://i.pravatar.cc/150?img=1',
                'password' => 'admin123'
            ],
            [
                'email' => 'modo@dce.com',
                'roles' => ['ROLE_MODERATOR'],
                'pseudo' => 'Moderator',
                'avatar' => 'https://i.pravatar.cc/150?img=2',
                'password' => 'modo123'
            ],
            [
                'email' => 'bruce@dce.com',
                'roles' => ['ROLE_USER'],
                'pseudo' => 'BruceW',
                'avatar' => 'https://i.pravatar.cc/150?img=3',
                'password' => 'user123'
            ],
            [
                'email' => 'clark@dce.com',
                'roles' => ['ROLE_USER'],
                'pseudo' => 'ClarkK',
                'avatar' => 'https://i.pravatar.cc/150?img=4',
                'password' => 'user123'
            ],
            [
                'email' => 'diana@dce.com',
                'roles' => ['ROLE_USER'],
                'pseudo' => 'DianaP',
                'avatar' => 'https://i.pravatar.cc/150?img=5',
                'password' => 'user123'
            ],
            [
                'email' => 'barry@dce.com',
                'roles' => ['ROLE_USER'],
                'pseudo' => 'BarryA',
                'avatar' => 'https://i.pravatar.cc/150?img=6',
                'password' => 'user123'
            ],
            [
                'email' => 'hal@dce.com',
                'roles' => ['ROLE_USER'],
                'pseudo' => 'HalJ',
                'avatar' => 'https://i.pravatar.cc/150?img=7',
                'password' => 'user123'
            ],
            [
                'email' => 'arthur@dce.com',
                'roles' => ['ROLE_USER'],
                'pseudo' => 'ArthurC',
                'avatar' => 'https://i.pravatar.cc/150?img=8',
                'password' => 'user123'
            ],
            [
                'email' => 'joker@dce.com',
                'roles' => ['ROLE_USER'],
                'pseudo' => 'JokerMad',
                'avatar' => 'https://i.pravatar.cc/150?img=9',
                'password' => 'user123'
            ],
            [
                'email' => 'harley@dce.com',
                'roles' => ['ROLE_USER'],
                'pseudo' => 'HarleyQ',
                'avatar' => 'https://i.pravatar.cc/150?img=10',
                'password' => 'user123'
            ]
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPseudo($userData['pseudo']);
            $user->setAvatar($userData['avatar']);
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $userData['password']
                )
            );
            $user->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 365))));
            $user->setLastLogin(new \DateTime(sprintf('-%d days', rand(0, 30))));

            $manager->persist($user);
            
            // Définir une référence pour chaque utilisateur
            $this->addReference('user_' . strtolower($userData['pseudo']), $user);
        }

        $manager->flush();
    }
} 