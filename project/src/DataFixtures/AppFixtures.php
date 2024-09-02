<?php
// src/DataFixtures/AppFixtures.php
// src/DataFixtures/AppFixtures.php

namespace App\DataFixtures;

use App\Entity\CategoriesType;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\ForumCategory;
use App\Entity\Forum;
use App\Entity\Thread;
use App\Entity\Post;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create Roles
        $roles = ['Hero' => '#1e90ff', 'Villain' => '#dc143c', 'Civilian' => '#32cd32'];
        $roleEntities = [];

        foreach ($roles as $roleName => $roleColor) {
            $role = new Role();
            $role->setName($roleName);
            $role->setColor($roleColor);
            $manager->persist($role);
            $roleEntities[$roleName] = $role;
        }

        // Create Users
        $userEntities = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();
            $user->setPseudo("User$i");
            $user->setEmail("user$i@example.com");
            $user->setAvatar("https://example.com/avatar/user$i.png");
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setCreatedAt(new \DateTimeImmutable());

            // Assign a random role to the user
            $user->addPlayerRole($roleEntities[array_rand($roleEntities)]);

            $manager->persist($user);
            $userEntities[] = $user;
        }

        $manager->flush();

// Create Types
$categoryTypes = ['special', 'general', 'archived'];
$categoryTypeEntities = [];

foreach ($categoryTypes as $categoryTypeName) {
    $categoryType = new CategoriesType();
    $categoryType->setName($categoryTypeName);
    $manager->persist($categoryType);
    $categoryTypeEntities[$categoryTypeName] = $categoryType;
}

$manager->flush();

// Create Categories
$categories = [
    ['name' => 'General Discussion', 'type' => 'general'],
    ['name' => 'News and Announcements', 'type' => 'general'],
    ['name' => 'Archives', 'type' => 'archived'],
];

$categoryEntities = [];

foreach ($categories as $categoryData) {
    $category = new ForumCategory();
    $category->setName($categoryData['name']);
    $category->setDescription("Description for {$categoryData['name']}");
    
    // Set the associated CategoriesType
    $categoryType = $categoryTypeEntities[$categoryData['type']];
    $category->setType($categoryType);
    
    $manager->persist($category);
    $categoryEntities[] = $category;
}

$manager->flush();


        // Create Forums with their categories
        $forumEntities = [];
        foreach ($categoryEntities as $category) {
            for ($i = 1; $i <= 2; $i++) {
                $forum = new Forum();
                $forum->setName("Forum $i in " . $category->getName());
                $forum->setDescription("Description for Forum $i");
                $forum->setCategory($category);
                $forum->setBanner("https://example.com/banner/forum$i.png");
                $forum->setCreatedAt(new \DateTimeImmutable());

                $manager->persist($forum);
                $forumEntities[] = $forum;

                // Create Threads for each main forum
                for ($j = 1; $j <= 2; $j++) {
                    $thread = new Thread();
                    $thread->setTitle("Thread $j in " . $forum->getName());
                    $thread->setForum($forum);
                    $thread->setAuthor($userEntities[array_rand($userEntities)]);
                    $thread->setCreatedAt(new \DateTimeImmutable());

                    $manager->persist($thread);

                    // Create Posts for each Thread
                    for ($k = 1; $k <= 3; $k++) {
                        $post = new Post();
                        $post->setContent("This is post $k in Thread $j in " . $forum->getName());
                        $post->setThread($thread);
                        $post->setAuthor($thread->getAuthor());
                        $post->setCreatedAt(new \DateTimeImmutable());

                        $manager->persist($post);
                    }
                }
            }
        }

        $manager->flush();

        // Create Subforums, ensuring they are linked to a parent forum and not directly to a category
        foreach ($forumEntities as $forum) {
            for ($m = 1; $m <= 2; $m++) {
                $subForum = new Forum();
                $subForum->setName("Subforum $m in " . $forum->getName());
                $subForum->setDescription("Description for Subforum $m");
                $subForum->setForum($forum); // Ensure subforum has a parent forum set
                $subForum->setBanner("https://example.com/banner/subforum$m.png");
                $subForum->setCreatedAt(new \DateTimeImmutable());

                $manager->persist($subForum);

                // Create Threads for each Subforum
                for ($n = 1; $n <= 2; $n++) {
                    $thread = new Thread();
                    $thread->setTitle("Thread $n in " . $subForum->getName());
                    $thread->setForum($subForum);
                    $thread->setAuthor($userEntities[array_rand($userEntities)]);
                    $thread->setCreatedAt(new \DateTimeImmutable());

                    $manager->persist($thread);

                    // Create Posts for each Thread
                    for ($p = 1; $p <= 3; $p++) {
                        $post = new Post();
                        $post->setContent("This is post $p in Thread $n in " . $subForum->getName());
                        $post->setThread($thread);
                        $post->setAuthor($thread->getAuthor());
                        $post->setCreatedAt(new \DateTimeImmutable());

                        $manager->persist($post);
                    }
                }
            }
        }

        $manager->flush();
    }
}
