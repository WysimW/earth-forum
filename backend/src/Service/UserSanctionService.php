<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserSanction;
use App\Repository\UserSanctionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\SecurityBundle\Security;

class UserSanctionService
{
    private EntityManagerInterface $entityManager;
    private UserSanctionRepository $sanctionRepository;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserSanctionRepository $sanctionRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->sanctionRepository = $sanctionRepository;
        $this->security = $security;
    }

    /**
     * Applique une nouvelle sanction à un utilisateur
     */
    public function applySanction(
        User $user,
        string $type,
        string $reason,
        ?\DateTime $expiresAt = null
    ): UserSanction {
        // Vérifier si l'utilisateur actuel est un modérateur
        $moderator = $this->security->getUser();
        if (!$moderator instanceof User || !in_array('ROLE_MODERATOR', $moderator->getRoles())) {
            throw new AccessDeniedException('Vous devez être modérateur pour appliquer une sanction.');
        }

        // Créer la nouvelle sanction
        $sanction = new UserSanction();
        $sanction
            ->setUser($user)
            ->setModerator($moderator)
            ->setType($type)
            ->setReason($reason)
            ->setExpiresAt($expiresAt)
            ->setIsActive(true);

        $this->entityManager->persist($sanction);
        $this->entityManager->flush();

        return $sanction;
    }

    /**
     * Révoque une sanction active
     */
    public function revokeSanction(UserSanction $sanction, string $reason): void
    {
        // Vérifier si l'utilisateur actuel est un modérateur
        $moderator = $this->security->getUser();
        if (!$moderator instanceof User || !in_array('ROLE_MODERATOR', $moderator->getRoles())) {
            throw new AccessDeniedException('Vous devez être modérateur pour révoquer une sanction.');
        }

        if (!$sanction->isActive()) {
            throw new \InvalidArgumentException('Cette sanction n\'est pas active.');
        }

        $sanction
            ->setIsActive(false)
            ->setRevokedAt(new \DateTime())
            ->setRevokedBy($moderator)
            ->setModeratorNotes($reason);

        $this->entityManager->flush();
    }

    /**
     * Vérifie si un utilisateur peut poster un message (n'a pas de ban de messagerie active)
     */
    public function canSendMessage(User $user): bool
    {
        return !$this->hasActiveSanction($user, UserSanction::TYPE_BAN_MESSAGING) &&
               !$this->hasActiveSanction($user, UserSanction::TYPE_FULL_BAN) &&
               !$this->hasActiveSanction($user, UserSanction::TYPE_MUTE);
    }

    /**
     * Vérifie si un utilisateur peut créer une discussion (n'a pas de ban de publication active)
     */
    public function canCreateDiscussion(User $user): bool
    {
        return !$this->hasActiveSanction($user, UserSanction::TYPE_BAN_POSTING) &&
               !$this->hasActiveSanction($user, UserSanction::TYPE_FULL_BAN);
    }

    /**
     * Vérifie si un utilisateur a une sanction active d'un type spécifique
     */
    public function hasActiveSanction(User $user, string $type): bool
    {
        return $this->sanctionRepository->hasActiveSanction($user, $type);
    }

    /**
     * Récupère la sanction active la plus restrictive pour un utilisateur
     */
    public function getMostRestrictiveSanction(User $user): ?UserSanction
    {
        return $this->sanctionRepository->findMostRestrictiveActiveSanction($user);
    }

    /**
     * Liste toutes les sanctions actives pour un utilisateur
     */
    public function getActiveSanctions(User $user): array
    {
        return $this->sanctionRepository->findActiveByUser($user);
    }
    
    /**
     * Nettoie les sanctions expirées et les marque comme inactives
     */
    public function cleanExpiredSanctions(): int
    {
        $expiredSanctions = $this->sanctionRepository->findExpiredActiveSanctions();
        $count = 0;
        
        foreach ($expiredSanctions as $sanction) {
            $sanction->setIsActive(false);
            $count++;
        }
        
        if ($count > 0) {
            $this->entityManager->flush();
        }
        
        return $count;
    }
    
    /**
     * Vérifie si un utilisateur peut accéder à la messagerie
     */
    public function canAccessMessaging(User $user): bool
    {
        return !$this->hasActiveSanction($user, UserSanction::TYPE_BAN_MESSAGING) &&
               !$this->hasActiveSanction($user, UserSanction::TYPE_FULL_BAN);
    }
    
    /**
     * Vérifie si un utilisateur peut poster sur le forum
     */
    public function canPostOnForum(User $user): bool
    {
        return !$this->hasActiveSanction($user, UserSanction::TYPE_BAN_POSTING) &&
               !$this->hasActiveSanction($user, UserSanction::TYPE_FULL_BAN);
    }
    
    /**
     * Vérifie si un utilisateur a accès au site (n'est pas banni complètement)
     */
    public function canAccessSite(User $user): bool
    {
        return !$this->hasActiveSanction($user, UserSanction::TYPE_FULL_BAN);
    }
    
    /**
     * Prolonge une sanction active
     */
    public function extendSanction(UserSanction $sanction, \DateTime $newExpiresAt, string $reason): void
    {
        // Vérifier si l'utilisateur actuel est un modérateur
        $moderator = $this->security->getUser();
        if (!$moderator instanceof User || !in_array('ROLE_MODERATOR', $moderator->getRoles())) {
            throw new AccessDeniedException('Vous devez être modérateur pour prolonger une sanction.');
        }

        if (!$sanction->isActive()) {
            throw new \InvalidArgumentException('Cette sanction n\'est pas active.');
        }
        
        // La nouvelle date d'expiration doit être ultérieure à la date actuelle
        if ($newExpiresAt <= new \DateTime()) {
            throw new \InvalidArgumentException('La nouvelle date d\'expiration doit être dans le futur.');
        }
        
        // Si la sanction a déjà une date d'expiration, vérifier que la nouvelle est ultérieure
        if ($sanction->getExpiresAt() && $newExpiresAt <= $sanction->getExpiresAt()) {
            throw new \InvalidArgumentException('La nouvelle date d\'expiration doit être ultérieure à la date actuelle.');
        }

        $sanction->setExpiresAt($newExpiresAt);
        
        // Ajouter une note sur la prolongation
        $notes = $sanction->getModeratorNotes() ? $sanction->getModeratorNotes() . "\n\n" : '';
        $notes .= sprintf(
            "[%s] Sanction prolongée jusqu'au %s par %s. Raison : %s",
            (new \DateTime())->format('d/m/Y H:i'),
            $newExpiresAt->format('d/m/Y H:i'),
            $moderator->getPseudo(),
            $reason
        );
        
        $sanction->setModeratorNotes($notes);
        
        $this->entityManager->flush();
    }
    
    /**
     * Vérifie et applique les restrictions de messagerie pour un utilisateur
     * 
     * @return array Informations sur les restrictions
     */
    public function checkMessagingRestrictions(User $user): array
    {
        $result = [
            'canAccessMessaging' => true,
            'canSendMessages' => true,
            'restrictionMessage' => null,
            'restrictionExpiry' => null,
            'restrictionType' => null
        ];
        
        // Vérifier le ban total d'abord
        if ($this->hasActiveSanction($user, UserSanction::TYPE_FULL_BAN)) {
            $sanction = $this->sanctionRepository->findActiveSanctionByType($user, UserSanction::TYPE_FULL_BAN);
            $result['canAccessMessaging'] = false;
            $result['canSendMessages'] = false;
            $result['restrictionMessage'] = 'Votre compte est actuellement banni.';
            $result['restrictionExpiry'] = $sanction?->getExpiresAt();
            $result['restrictionType'] = UserSanction::TYPE_FULL_BAN;
            return $result;
        }
        
        // Vérifier le ban de messagerie
        if ($this->hasActiveSanction($user, UserSanction::TYPE_BAN_MESSAGING)) {
            $sanction = $this->sanctionRepository->findActiveSanctionByType($user, UserSanction::TYPE_BAN_MESSAGING);
            $result['canAccessMessaging'] = false;
            $result['canSendMessages'] = false;
            $result['restrictionMessage'] = 'Vous êtes temporairement interdit d\'accès à la messagerie.';
            $result['restrictionExpiry'] = $sanction?->getExpiresAt();
            $result['restrictionType'] = UserSanction::TYPE_BAN_MESSAGING;
            return $result;
        }
        
        // Vérifier le mute
        if ($this->hasActiveSanction($user, UserSanction::TYPE_MUTE)) {
            $sanction = $this->sanctionRepository->findActiveSanctionByType($user, UserSanction::TYPE_MUTE);
            $result['canSendMessages'] = false;
            $result['restrictionMessage'] = 'Vous ne pouvez pas envoyer de messages temporairement.';
            $result['restrictionExpiry'] = $sanction?->getExpiresAt();
            $result['restrictionType'] = UserSanction::TYPE_MUTE;
        }
        
        return $result;
    }
} 