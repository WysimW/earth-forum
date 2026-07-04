<?php

namespace App\Controller\Api;

use App\Entity\MemberOfMonthMessage;
use App\Entity\SiteSetting;
use App\Entity\UserImportantSeen;
use App\Entity\User;
use App\Entity\UniverseCharacterOfMonth;
use App\Entity\UniverseMemberOfMonth;
use App\Entity\UserRegulationAcceptance;
use App\Repository\MemberOfMonthMessageRepository;
use App\Repository\SiteSettingRepository;
use App\Repository\UniverseCharacterOfMonthRepository;
use App\Repository\UniverseMemberOfMonthRepository;
use App\Repository\UserImportantSeenRepository;
use App\Repository\UniversRepository;
use App\Repository\UserRepository;
use App\Service\S3MediaUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ImportantController extends AbstractController
{
    private const SETTING_REGULATION_CONTENT = 'regulation_content';
    private const SETTING_REGULATION_VERSION = 'regulation_version';
    private const SETTING_GUIDE_CONTENT = 'guide_content';
    private const SETTING_GUIDE_VERSION = 'guide_version';
    private const SETTING_VOTE_URL = 'vote_url';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SiteSettingRepository $siteSettingRepository,
        private readonly UniversRepository $universRepository,
        private readonly UserRepository $userRepository,
        private readonly UniverseMemberOfMonthRepository $memberOfMonthRepository,
        private readonly UniverseCharacterOfMonthRepository $characterOfMonthRepository,
        private readonly MemberOfMonthMessageRepository $memberMessageRepository,
        private readonly UserImportantSeenRepository $importantSeenRepository,
        private readonly S3MediaUrlResolver $s3MediaUrlResolver,
    ) {
    }

    #[Route('/api/sidebar/important', name: 'api_sidebar_important', methods: ['GET'])]
    public function getSidebarImportant(Request $request): JsonResponse
    {
        $universeSlug = (string) $request->query->get('universe', '');

        return new JsonResponse([
            'items' => [
                [
                    'key' => 'reglement',
                    'label' => 'Règlement',
                    'url' => '/reglement',
                    'isExternal' => false,
                ],
                [
                    'key' => 'mode_emploi',
                    'label' => 'Mode d’emploi',
                    'url' => '/mode-emploi',
                    'isExternal' => false,
                ],
                [
                    'key' => 'member_of_month',
                    'label' => 'Membre du mois',
                    'url' => $universeSlug !== '' ? sprintf('/member-of-month/%s', $universeSlug) : '/member-of-month',
                    'isExternal' => false,
                ],
                [
                    'key' => 'character_of_month',
                    'label' => 'Personnage du mois',
                    'url' => $universeSlug !== '' ? sprintf('/character-of-month/%s', $universeSlug) : '/character-of-month',
                    'isExternal' => false,
                ],
                [
                    'key' => 'rp_activities',
                    'label' => 'Events & Missions',
                    'url' => '/rp-activities',
                    'isExternal' => false,
                ],
                [
                    'key' => 'vote',
                    'label' => 'Votez pour nous',
                    'url' => $this->siteSettingRepository->getValue(self::SETTING_VOTE_URL, '#'),
                    'isExternal' => true,
                ],
            ],
        ]);
    }

    #[Route('/api/reglement', name: 'api_regulation_get', methods: ['GET'])]
    public function getRegulation(): JsonResponse
    {
        $version = $this->siteSettingRepository->getValue(self::SETTING_REGULATION_VERSION, '1');
        $content = $this->siteSettingRepository->getValue(self::SETTING_REGULATION_CONTENT, '');

        $accepted = false;
        if ($this->getUser()) {
            $latestAcceptance = $this->entityManager->getRepository(UserRegulationAcceptance::class)
                ->findOneBy(['user' => $this->getUser()], ['acceptedAt' => 'DESC']);
            $accepted = $latestAcceptance?->getRegulationVersion() === $version;
        }

        return new JsonResponse([
            'version' => $version,
            'content' => $content,
            'accepted' => $accepted,
        ]);
    }

    #[Route('/api/reglement/accept', name: 'api_regulation_accept', methods: ['POST'])]
    public function acceptRegulation(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $version = $this->siteSettingRepository->getValue(self::SETTING_REGULATION_VERSION, '1');
        $acceptance = new UserRegulationAcceptance();
        $acceptance->setUser($user);
        $acceptance->setRegulationVersion($version);
        $acceptance->setAcceptedAt(new \DateTimeImmutable());

        $this->entityManager->persist($acceptance);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'accepted']);
    }

    #[Route('/api/mode-emploi', name: 'api_guide_get', methods: ['GET'])]
    public function getGuide(): JsonResponse
    {
        return new JsonResponse([
            'version' => $this->siteSettingRepository->getValue(self::SETTING_GUIDE_VERSION, '1'),
            'content' => $this->siteSettingRepository->getValue(self::SETTING_GUIDE_CONTENT, ''),
        ]);
    }

    #[Route('/api/member-of-month/{universeSlug}', name: 'api_member_of_month_latest', methods: ['GET'])]
    public function getLatestMemberOfMonth(string $universeSlug): JsonResponse
    {
        $universe = $this->universRepository->findOneBy(['slug' => $universeSlug]);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entry = $this->memberOfMonthRepository->findOneBy(
            ['universe' => $universe],
            ['year' => 'DESC', 'month' => 'DESC']
        );

        $isUnread = false;
        if ($entry && $this->getUser()) {
            $seen = $this->importantSeenRepository->findOneFor(
                $this->getUser(),
                $universe,
                UserImportantSeen::CONTENT_MEMBER_OF_MONTH
            );
            $isUnread = !$seen || (int) $seen->getLastSeenEntryId() !== (int) $entry->getId();
        }

        return new JsonResponse([
            'entry' => $entry ? $this->serializeMemberEntry($entry) : null,
            'isUnread' => $isUnread,
        ]);
    }

    #[Route('/api/member-of-month/{universeSlug}/history', name: 'api_member_of_month_history', methods: ['GET'])]
    public function getMemberOfMonthHistory(string $universeSlug): JsonResponse
    {
        $universe = $this->universRepository->findOneBy(['slug' => $universeSlug]);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entries = $this->memberOfMonthRepository->findBy(
            ['universe' => $universe],
            ['year' => 'DESC', 'month' => 'DESC']
        );

        return new JsonResponse([
            'items' => array_map(fn (UniverseMemberOfMonth $entry) => $this->serializeMemberEntry($entry), $entries),
        ]);
    }

    #[Route('/api/member-of-month/{id}/messages', name: 'api_member_of_month_messages', methods: ['GET'])]
    public function getMemberOfMonthMessages(int $id): JsonResponse
    {
        $entry = $this->memberOfMonthRepository->find($id);
        if (!$entry) {
            return new JsonResponse(['error' => 'Entrée introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $messages = $this->memberMessageRepository->findBy(
            ['memberOfMonth' => $entry],
            ['createdAt' => 'DESC']
        );

        $memberUser = $entry->getUser();
        $memberUserId = $memberUser?->getId();
        $messageAuthorIds = array_values(array_unique(array_filter(array_map(
            static fn (MemberOfMonthMessage $message): ?int => $message->getUser()?->getId(),
            $messages
        ))));
        $commonThreadsByUserId = ($memberUser && $messageAuthorIds !== [])
            ? $this->getCommonThreadCountsByUser($memberUser, $messageAuthorIds, $entry->getUniverse()?->getId())
            : [];

        return new JsonResponse([
            'items' => array_map(
                fn (MemberOfMonthMessage $message): array => [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'createdAt' => $message->getCreatedAt()?->format(\DateTimeInterface::ATOM),
                    'user' => [
                        'id' => $message->getUser()?->getId(),
                        'pseudo' => $message->getUser()?->getPseudo(),
                        'avatar' => $this->s3MediaUrlResolver->resolve($message->getUser()?->getAvatar()),
                    ],
                    'commonThreadsCount' => ($message->getUser() && $memberUserId !== null && $message->getUser()->getId() !== $memberUserId)
                        ? (int) ($commonThreadsByUserId[$message->getUser()->getId()] ?? 0)
                        : null,
                ],
                $messages
            ),
        ]);
    }

    #[Route('/api/member-of-month/{id}/messages', name: 'api_member_of_month_message_create', methods: ['POST'])]
    public function createMemberOfMonthMessage(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $entry = $this->memberOfMonthRepository->find($id);
        if (!$entry) {
            return new JsonResponse(['error' => 'Entrée introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        $content = trim((string) ($payload['content'] ?? ''));
        if ($content === '' || mb_strlen($content) > 800) {
            return new JsonResponse(['error' => 'Message invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $message = new MemberOfMonthMessage();
        $message->setMemberOfMonth($entry);
        $message->setUser($user);
        $message->setContent($content);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'created'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/character-of-month/{universeSlug}', name: 'api_character_of_month_latest', methods: ['GET'])]
    public function getLatestCharacterOfMonth(string $universeSlug): JsonResponse
    {
        $universe = $this->universRepository->findOneBy(['slug' => $universeSlug]);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entry = $this->characterOfMonthRepository->findOneBy(
            ['universe' => $universe],
            ['year' => 'DESC', 'month' => 'DESC']
        );

        $isUnread = false;
        if ($entry && $this->getUser()) {
            $seen = $this->importantSeenRepository->findOneFor(
                $this->getUser(),
                $universe,
                UserImportantSeen::CONTENT_CHARACTER_OF_MONTH
            );
            $isUnread = !$seen || (int) $seen->getLastSeenEntryId() !== (int) $entry->getId();
        }

        return new JsonResponse([
            'entry' => $entry ? $this->serializeCharacterEntry($entry) : null,
            'isUnread' => $isUnread,
        ]);
    }

    #[Route('/api/member-of-month/{universeSlug}/mark-seen', name: 'api_member_of_month_mark_seen', methods: ['POST'])]
    public function markMemberOfMonthSeen(string $universeSlug): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $universe = $this->universRepository->findOneBy(['slug' => $universeSlug]);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entry = $this->memberOfMonthRepository->findOneBy(
            ['universe' => $universe],
            ['year' => 'DESC', 'month' => 'DESC']
        );
        $entryId = $entry?->getId();

        $this->importantSeenRepository->markSeen(
            $user,
            $universe,
            UserImportantSeen::CONTENT_MEMBER_OF_MONTH,
            $entryId ? (int) $entryId : null
        );

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/api/character-of-month/{universeSlug}/mark-seen', name: 'api_character_of_month_mark_seen', methods: ['POST'])]
    public function markCharacterOfMonthSeen(string $universeSlug): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $universe = $this->universRepository->findOneBy(['slug' => $universeSlug]);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entry = $this->characterOfMonthRepository->findOneBy(
            ['universe' => $universe],
            ['year' => 'DESC', 'month' => 'DESC']
        );
        $entryId = $entry?->getId();

        $this->importantSeenRepository->markSeen(
            $user,
            $universe,
            UserImportantSeen::CONTENT_CHARACTER_OF_MONTH,
            $entryId ? (int) $entryId : null
        );

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/api/character-of-month/{universeSlug}/history', name: 'api_character_of_month_history', methods: ['GET'])]
    public function getCharacterOfMonthHistory(string $universeSlug): JsonResponse
    {
        $universe = $this->universRepository->findOneBy(['slug' => $universeSlug]);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entries = $this->characterOfMonthRepository->findBy(
            ['universe' => $universe],
            ['year' => 'DESC', 'month' => 'DESC']
        );

        return new JsonResponse([
            'items' => array_map(fn (UniverseCharacterOfMonth $entry) => $this->serializeCharacterEntry($entry), $entries),
        ]);
    }

    #[Route('/api/admin/site-settings/reglement', name: 'api_admin_regulation_update', methods: ['PUT'])]
    public function updateRegulation(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $content = (string) ($payload['content'] ?? '');

        $this->upsertSetting(self::SETTING_REGULATION_CONTENT, $content);
        $this->upsertSetting(self::SETTING_REGULATION_VERSION, sha1($content . microtime(true)));
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'saved']);
    }

    #[Route('/api/admin/site-settings/vote-url', name: 'api_admin_vote_url_update', methods: ['PUT'])]
    public function updateVoteUrl(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $url = trim((string) ($payload['url'] ?? ''));
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            return new JsonResponse(['error' => 'URL invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->upsertSetting(self::SETTING_VOTE_URL, $url);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'saved']);
    }

    #[Route('/api/admin/site-settings/mode-emploi', name: 'api_admin_guide_update', methods: ['PUT'])]
    public function updateGuide(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $content = (string) ($payload['content'] ?? '');

        $this->upsertSetting(self::SETTING_GUIDE_CONTENT, $content);
        $this->upsertSetting(self::SETTING_GUIDE_VERSION, sha1($content . microtime(true)));
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'saved']);
    }

    #[Route('/api/admin/member-of-month/stats', name: 'api_admin_member_of_month_stats', methods: ['GET'])]
    public function getMemberOfMonthStats(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $universeId = (int) $request->query->get('universe', 0);
        $scope = (string) $request->query->get('scope', 'month');
        $now = new \DateTimeImmutable();

        if ($universeId <= 0) {
            return new JsonResponse(['items' => []]);
        }

        if (!$this->isUniverseAllowed($universeId)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('u.id, u.pseudo, COUNT(p.id) as postsCount')
            ->from('App\Entity\Post', 'p')
            ->join('p.author', 'u')
            ->join('p.thread', 't')
            ->join('t.forum', 'f')
            ->leftJoin('f.parent', 'fp')
            ->where('(f.universe = :universeId OR fp.universe = :universeId)')
            ->setParameter('universeId', $universeId)
            ->groupBy('u.id')
            ->orderBy('postsCount', 'DESC')
            ->setMaxResults(20);

        if ($scope === 'month') {
            $qb->andWhere('p.createdAt >= :from')
                ->setParameter('from', $now->modify('first day of this month 00:00:00'));
        } elseif ($scope === 'year') {
            $qb->andWhere('p.createdAt >= :from')
                ->setParameter('from', $now->modify('first day of january ' . $now->format('Y') . ' 00:00:00'));
        }

        $rows = $qb->getQuery()->getArrayResult();

        return new JsonResponse([
            'items' => array_map(static fn (array $row) => [
                'id' => (int) $row['id'],
                'pseudo' => $row['pseudo'],
                'postsCount' => (int) $row['postsCount'],
            ], $rows),
        ]);
    }

    #[Route('/api/admin/member-of-month/select', name: 'api_admin_member_of_month_select', methods: ['POST'])]
    public function selectMemberOfMonth(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $universeId = (int) ($payload['universe_id'] ?? 0);
        $userId = (int) ($payload['user_id'] ?? 0);
        $year = (int) ($payload['year'] ?? date('Y'));
        $month = (int) ($payload['month'] ?? date('n'));
        $highlightText = isset($payload['highlight_text']) ? trim((string) $payload['highlight_text']) : null;

        if ($universeId <= 0 || $userId <= 0 || $year < 2000 || $month < 1 || $month > 12) {
            return new JsonResponse(['error' => 'Payload invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$this->isUniverseAllowed($universeId)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $universe = $this->universRepository->find($universeId);
        $user = $this->userRepository->find($userId);
        if (!$universe || !$user) {
            return new JsonResponse(['error' => 'Ressource introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entry = $this->memberOfMonthRepository->findOneBy([
            'universe' => $universe,
            'year' => $year,
            'month' => $month,
        ]) ?? new UniverseMemberOfMonth();

        $entry->setUniverse($universe);
        $entry->setUser($user);
        $entry->setYear($year);
        $entry->setMonth($month);
        $entry->setHighlightText($highlightText);
        $entry->setSelectedBy($this->getUser());
        $entry->setSelectedAt(new \DateTimeImmutable());

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'saved']);
    }

    #[Route('/api/admin/member-of-month/history', name: 'api_admin_member_of_month_history', methods: ['GET'])]
    public function getMemberOfMonthAdminHistory(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $universeId = (int) $request->query->get('universe', 0);
        if ($universeId <= 0) {
            return new JsonResponse(['items' => []]);
        }
        if (!$this->isUniverseAllowed($universeId)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $universe = $this->universRepository->find($universeId);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entries = $this->memberOfMonthRepository->findBy(
            ['universe' => $universe],
            ['year' => 'DESC', 'month' => 'DESC']
        );

        return new JsonResponse([
            'items' => array_map(fn (UniverseMemberOfMonth $entry) => $this->serializeMemberEntry($entry), $entries),
        ]);
    }

    #[Route('/api/admin/character-of-month', name: 'api_admin_character_of_month_create', methods: ['POST'])]
    public function createCharacterOfMonth(Request $request): JsonResponse
    {
        return $this->upsertCharacterOfMonth($request);
    }

    #[Route('/api/admin/character-of-month/{id}', name: 'api_admin_character_of_month_update', methods: ['PUT'])]
    public function updateCharacterOfMonth(int $id, Request $request): JsonResponse
    {
        return $this->upsertCharacterOfMonth($request, $id);
    }

    #[Route('/api/admin/character-of-month/history', name: 'api_admin_character_of_month_history', methods: ['GET'])]
    public function getCharacterOfMonthAdminHistory(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $universeId = (int) $request->query->get('universe', 0);
        if ($universeId <= 0) {
            return new JsonResponse(['items' => []]);
        }
        if (!$this->isUniverseAllowed($universeId)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $universe = $this->universRepository->find($universeId);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entries = $this->characterOfMonthRepository->findBy(
            ['universe' => $universe],
            ['year' => 'DESC', 'month' => 'DESC']
        );

        return new JsonResponse([
            'items' => array_map(fn (UniverseCharacterOfMonth $entry) => $this->serializeCharacterEntry($entry), $entries),
        ]);
    }

    private function upsertCharacterOfMonth(Request $request, ?int $id = null): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $universeId = (int) ($payload['universe_id'] ?? 0);
        $year = (int) ($payload['year'] ?? date('Y'));
        $month = (int) ($payload['month'] ?? date('n'));
        if ($universeId <= 0 || $year < 2000 || $month < 1 || $month > 12) {
            return new JsonResponse(['error' => 'Payload invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!$this->isUniverseAllowed($universeId)) {
            return new JsonResponse(['error' => 'Univers non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $requiredFields = ['first_name', 'last_name', 'nickname', 'alignment', 'powers', 'weaknesses', 'who_is_text', 'why_play_text'];
        foreach ($requiredFields as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                return new JsonResponse(['error' => sprintf('Le champ %s est requis', $field)], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        $universe = $this->universRepository->find($universeId);
        if (!$universe) {
            return new JsonResponse(['error' => 'Univers introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($id !== null) {
            $entry = $this->characterOfMonthRepository->find($id);
            if (!$entry) {
                return new JsonResponse(['error' => 'Entrée introuvable'], JsonResponse::HTTP_NOT_FOUND);
            }
        } else {
            $entry = $this->characterOfMonthRepository->findOneBy([
                'universe' => $universe,
                'year' => $year,
                'month' => $month,
            ]) ?? new UniverseCharacterOfMonth();
        }

        $entry->setUniverse($universe);
        $entry->setYear($year);
        $entry->setMonth($month);
        $entry->setFirstName(trim((string) $payload['first_name']));
        $entry->setLastName(trim((string) $payload['last_name']));
        $entry->setNickname(trim((string) $payload['nickname']));
        $entry->setAlignment(trim((string) $payload['alignment']));
        $entry->setPowers(trim((string) $payload['powers']));
        $entry->setWeaknesses(trim((string) $payload['weaknesses']));
        $entry->setImageUrl(trim((string) ($payload['image_url'] ?? '')) ?: null);
        $entry->setWhoIsText(trim((string) $payload['who_is_text']));
        $entry->setWhyPlayText(trim((string) $payload['why_play_text']));
        $entry->setQuickCreatePayload(is_array($payload['quick_create_payload'] ?? null) ? $payload['quick_create_payload'] : null);
        $entry->setSelectedBy($this->getUser());
        $entry->setSelectedAt(new \DateTimeImmutable());

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 'saved',
            'entry' => $this->serializeCharacterEntry($entry),
        ]);
    }

    private function upsertSetting(string $key, ?string $value): void
    {
        $setting = $this->siteSettingRepository->findOneBy(['settingKey' => $key]) ?? (new SiteSetting())->setSettingKey($key);
        $setting->setSettingValue($value);
        $this->entityManager->persist($setting);
    }

    private function isUniverseAllowed(int $universeId): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        foreach ($user->getAdminUniverses() as $universe) {
            if ((int) $universe->getId() === $universeId) {
                return true;
            }
        }

        return false;
    }

    private function serializeMemberEntry(UniverseMemberOfMonth $entry): array
    {
        return [
            'id' => $entry->getId(),
            'year' => $entry->getYear(),
            'month' => $entry->getMonth(),
            'highlightText' => $entry->getHighlightText(),
            'selectedAt' => $entry->getSelectedAt()?->format(\DateTimeInterface::ATOM),
            'universe' => [
                'id' => $entry->getUniverse()?->getId(),
                'name' => $entry->getUniverse()?->getName(),
                'slug' => $entry->getUniverse()?->getSlug(),
            ],
            'user' => [
                'id' => $entry->getUser()?->getId(),
                'pseudo' => $entry->getUser()?->getPseudo(),
                'avatar' => $this->s3MediaUrlResolver->resolve($entry->getUser()?->getAvatar()),
            ],
            'monthlyStats' => $this->buildMemberMonthlyStats($entry),
        ];
    }

    private function buildMemberMonthlyStats(UniverseMemberOfMonth $entry): array
    {
        $user = $entry->getUser();
        $universe = $entry->getUniverse();

        if (!$user || !$universe) {
            return [
                'postsCount' => 0,
                'participatedThreadsCount' => 0,
                'createdThreadsCount' => 0,
                'periodFrom' => null,
                'periodTo' => null,
                'topThreads' => [],
            ];
        }

        $now = new \DateTimeImmutable();
        $isCurrentMonth = (int) $now->format('Y') === $entry->getYear() && (int) $now->format('n') === $entry->getMonth();
        $referenceDate = $isCurrentMonth
            ? $now
            : (new \DateTimeImmutable(sprintf('%04d-%02d-01 23:59:59', $entry->getYear(), $entry->getMonth())))->modify('last day of this month');
        $periodStart = $referenceDate->modify('-30 days');
        $periodEnd = $referenceDate->modify('+1 second');

        $basePostsQb = $this->entityManager->createQueryBuilder()
            ->from('App\Entity\Post', 'p')
            ->join('p.thread', 't')
            ->join('t.forum', 'f')
            ->leftJoin('f.parent', 'fp')
            ->where('p.author = :user')
            ->andWhere('p.createdAt >= :periodStart')
            ->andWhere('p.createdAt < :periodEnd')
            ->andWhere('(f.universe = :universe OR fp.universe = :universe)')
            ->andWhere('f.type NOT IN (:excludedForumTypes)')
            ->andWhere('(fp.id IS NULL OR fp.type NOT IN (:excludedForumTypes))')
            ->setParameter('user', $user)
            ->setParameter('universe', $universe)
            ->setParameter('excludedForumTypes', ['hrp', 'important'])
            ->setParameter('periodStart', $periodStart)
            ->setParameter('periodEnd', $periodEnd);

        $postsCount = (int) (clone $basePostsQb)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $participatedThreadsCount = (int) (clone $basePostsQb)
            ->select('COUNT(DISTINCT t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $topThreadsRows = (clone $basePostsQb)
            ->select('t.id AS threadId, t.title AS threadTitle, t.slug AS threadSlug, COUNT(p.id) AS postsCount, MAX(p.createdAt) AS lastPostAt')
            ->groupBy('t.id, t.title, t.slug')
            ->orderBy('postsCount', 'DESC')
            ->addOrderBy('lastPostAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getArrayResult();

        $createdThreadsCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(tCreated.id)')
            ->from('App\Entity\Thread', 'tCreated')
            ->join('tCreated.forum', 'f')
            ->leftJoin('f.parent', 'fp')
            ->where('tCreated.author = :user')
            ->andWhere('tCreated.createdAt >= :periodStart')
            ->andWhere('tCreated.createdAt < :periodEnd')
            ->andWhere('(f.universe = :universe OR fp.universe = :universe)')
            ->andWhere('f.type NOT IN (:excludedForumTypes)')
            ->andWhere('(fp.id IS NULL OR fp.type NOT IN (:excludedForumTypes))')
            ->setParameter('user', $user)
            ->setParameter('universe', $universe)
            ->setParameter('excludedForumTypes', ['hrp', 'important'])
            ->setParameter('periodStart', $periodStart)
            ->setParameter('periodEnd', $periodEnd)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'postsCount' => $postsCount,
            'participatedThreadsCount' => $participatedThreadsCount,
            'createdThreadsCount' => $createdThreadsCount,
            'periodFrom' => $periodStart->format(\DateTimeInterface::ATOM),
            'periodTo' => $referenceDate->format(\DateTimeInterface::ATOM),
            'topThreads' => array_map(
                static fn (array $row) => [
                    'id' => (int) $row['threadId'],
                    'title' => (string) $row['threadTitle'],
                    'slug' => (string) $row['threadSlug'],
                    'postsCount' => (int) $row['postsCount'],
                ],
                $topThreadsRows
            ),
        ];
    }

    private function serializeCharacterEntry(UniverseCharacterOfMonth $entry): array
    {
        return [
            'id' => $entry->getId(),
            'year' => $entry->getYear(),
            'month' => $entry->getMonth(),
            'firstName' => $entry->getFirstName(),
            'lastName' => $entry->getLastName(),
            'nickname' => $entry->getNickname(),
            'alignment' => $entry->getAlignment(),
            'powers' => $entry->getPowers(),
            'weaknesses' => $entry->getWeaknesses(),
            'imageUrl' => $this->s3MediaUrlResolver->resolve($entry->getImageUrl()),
            'whoIsText' => $entry->getWhoIsText(),
            'whyPlayText' => $entry->getWhyPlayText(),
            'quickCreatePayload' => $entry->getQuickCreatePayload(),
            'selectedAt' => $entry->getSelectedAt()?->format(\DateTimeInterface::ATOM),
            'universe' => [
                'id' => $entry->getUniverse()?->getId(),
                'name' => $entry->getUniverse()?->getName(),
                'slug' => $entry->getUniverse()?->getSlug(),
            ],
        ];
    }

    /**
     * @param int[] $otherUserIds
     * @return array<int,int> [userId => commonThreadCount]
     */
    private function getCommonThreadCountsByUser(User $referenceUser, array $otherUserIds, ?int $universeId = null): array
    {
        $otherUserIds = array_values(array_unique(array_filter(array_map('intval', $otherUserIds))));
        if ($otherUserIds === []) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('otherAuthor.id AS userId', 'COUNT(DISTINCT t.id) AS commonThreadsCount')
            ->from('App\Entity\Thread', 't')
            ->join('t.posts', 'referencePosts')
            ->join('referencePosts.author', 'referenceAuthor')
            ->join('t.posts', 'otherPosts')
            ->join('otherPosts.author', 'otherAuthor')
            ->join('t.forum', 'f')
            ->leftJoin('f.parent', 'fp')
            ->where('referenceAuthor = :referenceUser')
            ->andWhere('otherAuthor.id IN (:otherUserIds)')
            ->andWhere('otherAuthor != :referenceUser')
            ->setParameter('referenceUser', $referenceUser)
            ->setParameter('otherUserIds', $otherUserIds)
            ->groupBy('otherAuthor.id');

        if ($universeId) {
            $qb->andWhere('(f.universe = :universeId OR fp.universe = :universeId)')
                ->setParameter('universeId', $universeId);
        }

        $rows = $qb->getQuery()->getArrayResult();
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['userId']] = (int) $row['commonThreadsCount'];
        }

        return $result;
    }
}

