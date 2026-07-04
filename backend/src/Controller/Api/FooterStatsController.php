<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class FooterStatsController extends AbstractController
{
    private const ONLINE_WINDOW_MINUTES = 5;
    private const ACTIVE_WINDOW_DAYS = 7;

    public function __construct(
        private UserRepository $userRepository,
        private PostRepository $postRepository,
        private EntityManagerInterface $entityManager,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('/api/footer/live-stats', name: 'api_footer_live_stats', methods: ['GET'])]
    public function liveStats(): JsonResponse
    {
        $this->touchAuthenticatedUserLastLogin();

        $now = new \DateTimeImmutable();
        $onlineSince = $now->modify(sprintf('-%d minutes', self::ONLINE_WINDOW_MINUTES));
        $activeSince = $now->modify(sprintf('-%d days', self::ACTIVE_WINDOW_DAYS));

        $onlineUsers = $this->userRepository->findRecentlyOnlineUsers($onlineSince, 10);
        $activeLast7Days = $this->userRepository->countActiveSince($activeSince);
        $activeLast7DaysUsers = $this->userRepository->findRecentlyActiveUsers($activeSince, 20);
        $totalPosts = $this->postRepository->count([]);

        return new JsonResponse([
            'online' => [
                'windowMinutes' => self::ONLINE_WINDOW_MINUTES,
                'count' => count($onlineUsers),
                'users' => array_map(
                    fn (User $user): array => [
                        'id' => $user->getId(),
                        'pseudo' => $user->getPseudo(),
                        'avatar' => $this->s3MediaUrlResolver->resolve($user->getAvatar()),
                        'lastLogin' => $user->getLastLogin()?->format(\DateTimeInterface::ATOM),
                    ],
                    $onlineUsers
                ),
            ],
            'activeLast7Days' => [
                'count' => $activeLast7Days,
                'users' => array_map(
                    fn (User $user): array => [
                        'id' => $user->getId(),
                        'pseudo' => $user->getPseudo(),
                        'avatar' => $this->s3MediaUrlResolver->resolve($user->getAvatar()),
                        'lastLogin' => $user->getLastLogin()?->format(\DateTimeInterface::ATOM),
                    ],
                    $activeLast7DaysUsers
                ),
            ],
            'forum' => [
                'totalPosts' => $totalPosts,
            ],
            'generatedAt' => $now->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function touchAuthenticatedUserLastLogin(): void
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return;
        }

        $lastLogin = $user->getLastLogin();
        $shouldUpdate = $lastLogin === null
            || $lastLogin < (new \DateTimeImmutable())->modify('-60 seconds');

        if (!$shouldUpdate) {
            return;
        }

        $user->setLastLogin(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}

