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
        $userPseudoTable = [
            'Batman', 'Superman', 'Wonder Woman', 'Flash', 'Lex Luthor', 'Joker', 'Cheetah', 'Reverse-Flash', 'Lois Lane', 'Darkseid'
        ];
        $userAvatarTable = [
            'https://i.postimg.cc/C57P5bxw/batfixture.png',
            'https://i.postimg.cc/66Rq4YKH/supermanfixture.png',
            'https://i.postimg.cc/BQTR9GCT/wwfixture.png',
            'https://i.postimg.cc/Dwb5G9CW/flashfixture.png',
            'https://i.postimg.cc/Px0hKpGb/Lexfixture.png',
            'https://i.postimg.cc/ryBdhzMw/Jokerfixture.png',
            'https://i.postimg.cc/WbTHDB4p/cheetahfixture.png',
            'https://i.postimg.cc/MTRQ1ZDn/reverseflash.png',
            'https://i.postimg.cc/XJRDggCn/loisfixture.png',
            'https://i.postimg.cc/QthFK26D/darkseidfixture.png',
        ];

        for ($i = 0; $i <= 9; $i++) {
            $user = new User();
            $user->setPseudo($userPseudoTable[$i]);
            $user->setEmail("user$i@example.com");
            $user->setAvatar($userAvatarTable[$i]);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setCreatedAt(new \DateTimeImmutable());

            // Assign a random role to the user
            $user->addPlayerRole($roleEntities[array_rand($roleEntities)]);

            $manager->persist($user);
            $userEntities[] = $user;
        }

        $manager->flush();

// Create Types
$categoryTypes = ['special', 'general', 'archived', 'roleplay', 'information'];
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
    ['name' => 'Information', 'type' => 'information'],
    ['name' => 'Personnages', 'type' => 'special'],
    ['name' => 'Terre', 'type' => 'roleplay'],
    ['name' => 'Archives', 'type' => 'archived'],
];



$categoryEntities = [];
$o=1;
foreach ($categories as $categoryData) {
    $category = new ForumCategory();
    $category->setName($categoryData['name']);
    $category->setDescription("Description for {$categoryData['name']}");
    
    // Set the associated CategoriesType
    $categoryType = $categoryTypeEntities[$categoryData['type']];
    $category->setType($categoryType);
    $category->setHomeOrder($o);
    
    $manager->persist($category);
    $categoryEntities[] = $category;
    $o++;
}

$manager->flush();
$forumBannerTable= [
'https://cdn.midjourney.com/b7c38aa9-05ea-4d3f-b603-d6fbd28463fb/0_0.png',
'https://cdn.midjourney.com/419cdf20-b270-4914-9108-5db993177def/0_2.png',
'https://cdn.midjourney.com/d815adc1-b74d-481c-b685-6650e4811c0d/0_0.png',
'https://cdn.midjourney.com/ed4b9a11-669b-48dd-808d-471bdd2058e3/0_0.png',
'https://cdn.midjourney.com/c93cf31d-4666-4e9d-8b23-2005e084b258/0_0.png',
'https://cdn.midjourney.com/a97af741-7652-4d31-9185-d46d9bd46863/0_1.png',
'https://cdn.midjourney.com/34b06ed4-61c3-42fa-a769-8a11c86a607b/0_0.png',
'https://cdn.midjourney.com/08a7a952-bf55-4465-b925-391897ca3030/0_2.png',
'https://cdn.midjourney.com/06797d63-a94e-4f0f-92a0-b8a1cb70d72c/0_1.png',
'https://cdn.midjourney.com/dd44c5b7-840a-41d3-a62a-9736b7b9faa3/0_0.png',
'https://cdn.midjourney.com/f7e28784-4179-4d34-9109-fadc717335d4/0_0.png',
'https://cdn.midjourney.com/2c63f29c-9baf-46ab-bfee-3390d5da0eb2/0_0.png',
'https://cdn.midjourney.com/29d70ab8-1b55-4000-b942-64e3a5be893f/0_0.png',
'https://cdn.midjourney.com/f234c244-07e0-4f1d-aed5-382651e382ae/0_0.png',
];
$forumNameTable= [
    'Règlement',
    'Informations',
    'Aventures',
    'Présentation',
    'Demande au staff',
    'Forum à supprimer',
    'Métropolis',
    'Gotham City',
    'Central City',
    'Archives',
    'Forum',
    'Forum',
    'Forum',
    'Forum',
    ];
$m=0;
        // Create Forums with their categories
        $forumEntities = [];
        foreach ($categoryEntities as $category) {
            for ($i = 1; $i <= 3; $i++) {
                $forum = new Forum();
                $forum->setName($forumNameTable[$m]);
                $forum->setDescription("Description for Forum $i");
                $forum->setCategory($category);
                $forum->setBanner($forumBannerTable[$m]);
                $forum->setCreatedAt(new \DateTimeImmutable());

                $manager->persist($forum);
                $forumEntities[] = $forum;
                $m++;
                // Create Threads for each main forum
                for ($j = 1; $j <= 20; $j++) {
                    $thread = new Thread();
                    $thread->setTitle("Thread $j in " . $forum->getName());
                    $thread->setForum($forum);
                    $thread->setAuthor($userEntities[array_rand($userEntities)]);
                    $thread->setCreatedAt(new \DateTimeImmutable());

                    $manager->persist($thread);

                    // Create Posts for each Thread
                    for ($k = 1; $k <= 20; $k++) {
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
                for ($n = 1; $n <= 10; $n++) {
                    $thread = new Thread();
                    $thread->setTitle("Thread $n in " . $subForum->getName());
                    $thread->setForum($subForum);
                    $thread->setAuthor($userEntities[array_rand($userEntities)]);
                    $thread->setCreatedAt(new \DateTimeImmutable());

                    $manager->persist($thread);

                    // Create Posts for each Thread
                    for ($p = 1; $p <= 10; $p++) {
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
