<?php

namespace App\Service\ForumActif;

use App\Entity\Forum;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ForumActifImporter
{
    /** @var array<string, string> */
    private array $forumMapping;

    /** @var array<string, Forum> */
    private array $forumCache = [];

    /** @var array<string, User> */
    private array $userCache = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly string $mappingFile,
    ) {
        $this->forumMapping = $this->loadForumMapping();
    }

    /**
     * @return array{threads: int, posts: int, skipped: int, unmappedForums: list<string>}
     */
    public function importYear(
        int $year,
        string $outputDir,
        ForumActifState $state,
        SymfonyStyle $io,
        bool $dryRun = false,
    ): array {
        $threadsDir = rtrim($outputDir, '/') . '/' . $year . '/threads';
        if (!is_dir($threadsDir)) {
            throw new \RuntimeException(sprintf('Aucun export trouvé pour %d dans %s. Lancez d\'abord --export.', $year, $outputDir));
        }

        $files = glob($threadsDir . '/t*.json') ?: [];
        sort($files);

        if ($files === []) {
            throw new \RuntimeException(sprintf('Aucun fichier thread dans %s', $threadsDir));
        }

        $stats = ['threads' => 0, 'posts' => 0, 'skipped' => 0, 'unmappedForums' => []];
        $unmapped = [];

        $io->progressStart(count($files));

        foreach ($files as $file) {
            $io->progressAdvance();

            /** @var array<string, mixed>|null $payload */
            $payload = json_decode((string) file_get_contents($file), true);
            if (!is_array($payload)) {
                continue;
            }

            $sourceThreadId = (int) ($payload['source']['threadId'] ?? 0);
            if ($sourceThreadId <= 0) {
                continue;
            }

            /** @var array<string, mixed>|null $forumData */
            $forumData = $payload['forum'] ?? null;
            $forum = $this->resolveForum(is_array($forumData) ? $forumData : []);
            if ($forum === null) {
                $slug = is_array($forumData) ? (string) ($forumData['slug'] ?? 'inconnu') : 'inconnu';
                $unmapped[$slug] = $slug;
                ++$stats['skipped'];
                continue;
            }

            $posts = is_array($payload['posts'] ?? null) ? $payload['posts'] : [];
            if ($posts === []) {
                continue;
            }

            $localThreadId = $state->getLocalThreadId($sourceThreadId);
            $thread = $localThreadId ? $this->entityManager->find(Thread::class, $localThreadId) : null;

            if (!$thread instanceof Thread) {
                $firstPost = $posts[0];
                $author = $this->resolveUser((string) ($firstPost['author'] ?? 'Inconnu'));

                $thread = new Thread();
                $thread->setTitle((string) ($payload['title'] ?? 'Sans titre'));
                $thread->setForum($forum);
                $thread->setAuthor($author);
                $thread->setType($forum->isRoleplay() ? 'roleplay' : 'hrp');
                $thread->setSlug($this->buildUniqueThreadSlug((string) ($payload['title'] ?? 'sans-titre')));

                if (!empty($firstPost['postedAt'])) {
                    $thread->setCreatedAt(new \DateTimeImmutable($firstPost['postedAt']));
                }

                if (!$dryRun) {
                    $this->entityManager->persist($thread);
                    $this->entityManager->flush();
                    $state->setLocalThreadId($sourceThreadId, (int) $thread->getId());
                    $state->save();
                }

                ++$stats['threads'];
            }

            foreach ($posts as $postData) {
                if (!is_array($postData)) {
                    continue;
                }

                $sourcePostId = (int) ($postData['sourceId'] ?? 0);
                if ($sourcePostId <= 0 || $state->isPostImported($sourcePostId)) {
                    continue;
                }

                $author = $this->resolveUser((string) ($postData['author'] ?? 'Inconnu'));
                $post = new Post();
                $post->setThread($thread);
                $post->setAuthor($author);
                $post->setContent((string) ($postData['contentHtml'] ?? ''));
                $post->setType($forum->isRoleplay() ? 'roleplay' : 'normal');

                if (!empty($postData['postedAt'])) {
                    $post->setCreatedAt(new \DateTimeImmutable($postData['postedAt']));
                }

                if (!empty($postData['editedAt'])) {
                    $post->setEditedAt(new \DateTimeImmutable($postData['editedAt']));
                }

                if (!$dryRun) {
                    $this->entityManager->persist($post);
                    $this->entityManager->flush();
                    $state->setLocalPostId($sourcePostId, (int) $post->getId());
                    $state->save();
                }

                ++$stats['posts'];
            }
        }

        $io->progressFinish();
        $stats['unmappedForums'] = array_values($unmapped);

        return $stats;
    }

    /**
     * @param array<string, mixed> $forumData
     */
    private function resolveForum(array $forumData): ?Forum
    {
        $sourceSlug = (string) ($forumData['slug'] ?? '');
        if ($sourceSlug === '') {
            return null;
        }

        if (isset($this->forumCache[$sourceSlug])) {
            return $this->forumCache[$sourceSlug];
        }

        $targetSlug = $this->forumMapping[$sourceSlug] ?? $sourceSlug;
        $forum = $this->entityManager->getRepository(Forum::class)->findOneBy(['slug' => $targetSlug]);

        if (!$forum && isset($forumData['name'])) {
            $forum = $this->entityManager->getRepository(Forum::class)->findOneBy(['name' => $forumData['name']]);
        }

        if ($forum) {
            $this->forumCache[$sourceSlug] = $forum;
        }

        return $forum;
    }

    private function resolveUser(string $pseudo): User
    {
        $pseudo = trim($pseudo);
        if ($pseudo === '') {
            $pseudo = 'Inconnu';
        }

        if (isset($this->userCache[$pseudo])) {
            return $this->userCache[$pseudo];
        }

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['pseudo' => $pseudo]);
        if ($existing instanceof User) {
            return $this->userCache[$pseudo] = $existing;
        }

        $emailSlug = $this->slugger->slug($pseudo)->lower()->toString();
        $user = new User();
        $user->setPseudo($pseudo);
        $user->setEmail(sprintf('forumactif.%s@import.local', $emailSlug !== '' ? $emailSlug : 'inconnu'));
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->userCache[$pseudo] = $user;
    }

    private function buildUniqueThreadSlug(string $title): string
    {
        $baseSlug = $this->slugger->slug($title)->lower()->toString();
        if ($baseSlug === '') {
            $baseSlug = 'thread';
        }

        $slug = $baseSlug;
        $counter = 1;
        while ($this->threadSlugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            ++$counter;
        }

        return $slug;
    }

    private function threadSlugExists(string $slug): bool
    {
        return $this->entityManager->getRepository(Thread::class)->findOneBy(['slug' => $slug]) instanceof Thread;
    }

    /** @return array<string, string> */
    private function loadForumMapping(): array
    {
        if (!is_file($this->mappingFile)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($this->mappingFile), true);
        if (!is_array($data)) {
            return [];
        }

        unset($data['_comment']);

        /** @var array<string, string> $mapping */
        $mapping = [];
        foreach ($data as $source => $target) {
            if (is_string($source) && is_string($target)) {
                $mapping[$source] = $target;
            }
        }

        return $mapping;
    }
}
