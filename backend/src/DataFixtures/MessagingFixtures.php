<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Univers;
use App\Entity\Messaging\Message;
use App\Repository\UserRepository;
use App\Entity\Messaging\Conversation;
use App\Repository\UniversRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Entity\Messaging\ConversationParticipant;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class MessagingFixtures extends Fixture implements FixtureGroupInterface
{
    private $userRepository;
    private $universRepository;

    // Données prédéfinies pour éviter d'utiliser Faker
    private $messageContents = [
        "Bonjour, comment ça va ?",
        "Est-ce que quelqu'un pourrait m'aider avec un problème ?",
        "Je viens de terminer la mission. Tout s'est bien passé !",
        "On se retrouve où et quand pour la prochaine rencontre ?",
        "Merci pour ton aide, j'apprécie vraiment.",
        "Je ne comprends pas ce qui s'est passé lors de la dernière réunion.",
        "Pouvez-vous partager les informations sur le prochain événement ?\n\nJ'ai besoin de prévoir mon emploi du temps.",
        "Le nouveau costume est incroyable, tu devrais vraiment venir voir ça !",
        "J'ai trouvé quelque chose d'intéressant sur cette affaire. Voici le lien : https://www.example.com/info",
        "Je serai absent pendant quelques jours, ne comptez pas sur moi.",
        "Quelqu'un a-t-il des nouvelles de Batman ? Il ne répond pas à mes messages.",
        "Mission accomplie. Les détails sont confidentiels, mais tout s'est déroulé comme prévu.",
        "Je n'ai pas pu assister à la réunion. Quelqu'un peut-il me faire un résumé ?",
        "Attention à tous ! Il y a une menace importante dans le secteur nord de la ville. Soyez prudents.",
        "Qui veut se joindre à moi pour patrouiller ce soir ?",
        "J'ai besoin de renforts immédiatement à l'adresse suivante : 42 Avenue du Paradis.",
        "Pouvons-nous reporter notre rencontre à la semaine prochaine ? J'ai un empêchement.",
        "Je viens d'obtenir de nouvelles informations cruciales sur notre ennemi.",
        "Merci à tous pour votre participation à la mission d'hier soir. Excellent travail d'équipe !",
        "Je propose une réunion stratégique demain à 18h. Qui est disponible ?",
        "Quelqu'un a-t-il vu mon équipement ? Je l'ai perdu pendant notre dernière sortie.",
        "Nous devons revoir notre approche. La méthode actuelle n'est pas efficace.",
        "Félicitations pour ta promotion ! Tu le mérites vraiment.",
        "Est-ce que quelqu'un peut me passer les coordonnées du nouveau contact ?",
        "J'ai une nouvelle piste pour notre enquête. Rencontrons-nous pour en discuter.",
    ];

    private $conversationNames = [
        "Équipe d'intervention",
        "Planification stratégique",
        "Réunion hebdomadaire",
        "Groupe d'enquête",
        "Coordination des héros",
        "Équipe de surveillance",
        "Renseignements confidentiels",
        "Alerte générale",
        "Entraînement spécial",
        "Alliance des justiciers",
    ];

    public function __construct(UserRepository $userRepository, UniversRepository $universRepository)
    {
        $this->userRepository = $userRepository;
        $this->universRepository = $universRepository;
    }

    public static function getGroups(): array
    {
        return ['messaging'];
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->userRepository->findAll();
        $universes = $this->universRepository->findAll();

        // S'assurer qu'il y a au moins 5 utilisateurs dans la base de données
        if (count($users) < 5) {
            throw new \Exception('Il faut au moins 5 utilisateurs en base de données pour générer les fixtures de messagerie');
        }

        // Création de 3 conversations privées (entre 2 personnes)
        $this->createPrivateConversations($manager, $users, 3);

        // Création de 2 conversations de groupe (entre 3 à 6 personnes)
        $this->createGroupConversations($manager, $users, $universes, 2);

        // Création d'une conversation publique (tous les utilisateurs)
        $this->createPublicConversation($manager, $users, $universes);

        $manager->flush();
    }

    /**
     * Crée des conversations privées entre 2 utilisateurs
     */
    private function createPrivateConversations(ObjectManager $manager, array $users, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            // Sélection de 2 utilisateurs aléatoires différents
            $userIndices = array_rand($users, 2);
            $creator = $users[$userIndices[0]];
            $recipient = $users[$userIndices[1]];

            $conversation = new Conversation();
            $conversation->setName("Discussion privée");
            $conversation->setCreator($creator);
            $conversation->setType(Conversation::TYPE_PRIVATE);
            
            // Créer une date dans le dernier mois
            $createdAt = new \DateTimeImmutable('-' . mt_rand(20, 30) . ' days');
            $conversation->setCreatedAt($createdAt);
            $conversation->setUpdatedAt($createdAt);
            
            $manager->persist($conversation);

            // Ajout des participants
            $creatorParticipant = new ConversationParticipant();
            $creatorParticipant->setConversation($conversation);
            $creatorParticipant->setUser($creator);
            $creatorParticipant->setRole(ConversationParticipant::ROLE_ADMIN);
            // On ne fixe pas encore la date de dernière lecture
            $manager->persist($creatorParticipant);

            $recipientParticipant = new ConversationParticipant();
            $recipientParticipant->setConversation($conversation);
            $recipientParticipant->setUser($recipient);
            $recipientParticipant->setRole(ConversationParticipant::ROLE_MEMBER);
            // On ne fixe pas encore la date de dernière lecture
            $manager->persist($recipientParticipant);

            // Création de messages dans cette conversation
            $messageCount = mt_rand(8, 15); // Assez de messages pour avoir des non lus
            $messageDates = $this->createMessages($manager, $conversation, [$creator, $recipient], $messageCount);
            
            // Maintenant, on peut fixer la dernière lecture à ~60% des messages pour garantir des non lus
            $midpointIndex = (int)($messageCount * 0.6);
            $lastReadDate = $messageDates[$midpointIndex];
            
            // Pour le créateur, dernier message lu à ~60% de la conversation
            $creatorParticipant->setLastReadAt($lastReadDate);
            // Pour le destinataire, dernier message lu à ~40% de la conversation
            $earlierReadDate = $messageDates[(int)($messageCount * 0.4)];
            $recipientParticipant->setLastReadAt($earlierReadDate);
        }
    }

    /**
     * Crée des conversations de groupe entre 3 à 6 utilisateurs
     */
    private function createGroupConversations(ObjectManager $manager, array $users, array $universes, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            // Sélection de 3 à 6 utilisateurs aléatoires
            $nbParticipants = mt_rand(3, min(6, count($users)));
            $userIndices = array_rand($users, $nbParticipants);
            $participants = [];
            
            // Traiter userIndices comme un tableau même s'il n'y a qu'un élément
            if (!is_array($userIndices)) {
                $userIndices = [$userIndices];
            }
            
            foreach ($userIndices as $index) {
                $participants[] = $users[$index];
            }
            
            $creator = $participants[0];
            
            // Sélection d'un univers aléatoire (si disponible)
            $universe = !empty($universes) ? $universes[array_rand($universes)] : null;

            $conversation = new Conversation();
            $conversation->setName($this->conversationNames[array_rand($this->conversationNames)]);
            $conversation->setCreator($creator);
            $conversation->setType(Conversation::TYPE_GROUP);
            
            // Créer une date dans le dernier mois
            $createdAt = new \DateTimeImmutable('-' . mt_rand(20, 30) . ' days');
            $conversation->setCreatedAt($createdAt);
            $conversation->setUpdatedAt($createdAt);
            
            $conversation->setUniverse($universe);
            
            $manager->persist($conversation);

            // Tableau pour stocker les entités participants
            $participantEntities = [];
            
            // Ajout des participants
            foreach ($participants as $index => $participant) {
                $participantEntity = new ConversationParticipant();
                $participantEntity->setConversation($conversation);
                $participantEntity->setUser($participant);
                
                // Le créateur est admin, les autres sont membres
                $role = ($index === 0) ? ConversationParticipant::ROLE_ADMIN : ConversationParticipant::ROLE_MEMBER;
                $participantEntity->setRole($role);
                
                // On ne fixe pas encore la dernière lecture
                $manager->persist($participantEntity);
                
                $participantEntities[] = $participantEntity;
            }

            // Création de messages dans cette conversation
            $messageCount = mt_rand(12, 25); // Plus de messages pour les groupes
            $messageDates = $this->createMessages($manager, $conversation, $participants, $messageCount);
            
            // Maintenant, pour chaque participant, on fixe une dernière lecture différente
            // pour s'assurer que chacun a des messages non lus
            foreach ($participantEntities as $index => $entity) {
                // Calculer un point entre 30% et 80% des messages selon le participant
                $percentage = 0.3 + ($index * 0.1); // 0.3, 0.4, 0.5, etc.
                if ($percentage > 0.8) $percentage = 0.8; // Max 80%
                
                $readIndex = (int)($messageCount * $percentage);
                if ($readIndex >= count($messageDates)) {
                    $readIndex = count($messageDates) - 1;
                }
                
                $entity->setLastReadAt($messageDates[$readIndex]);
            }
        }
    }

    /**
     * Crée une conversation publique avec tous les utilisateurs
     */
    private function createPublicConversation(ObjectManager $manager, array $users, array $universes): void
    {
        $creator = $users[array_rand($users)];
        $universe = !empty($universes) ? $universes[array_rand($universes)] : null;

        $conversation = new Conversation();
        $conversation->setName("Annonces " . ($universe ? "- " . $universe->getName() : "générales"));
        $conversation->setCreator($creator);
        $conversation->setType(Conversation::TYPE_PUBLIC);
        
        // Créer une date d'il y a 2 mois
        $createdAt = new \DateTimeImmutable('-' . mt_rand(45, 60) . ' days');
        $conversation->setCreatedAt($createdAt);
        
        // Date de mise à jour plus récente
        $updatedAt = new \DateTime('-' . mt_rand(1, 5) . ' days');
        $conversation->setUpdatedAt($updatedAt);
        
        $conversation->setUniverse($universe);
        
        $manager->persist($conversation);

        // Tableau pour stocker les entités participants
        $participantEntities = [];
        
        // Ajout de tous les utilisateurs comme participants
        foreach ($users as $index => $user) {
            $participant = new ConversationParticipant();
            $participant->setConversation($conversation);
            $participant->setUser($user);
            
            // Le créateur est admin, quelques modérateurs, le reste sont membres
            if ($user === $creator) {
                $role = ConversationParticipant::ROLE_ADMIN;
            } elseif ($index % 7 === 0) { // ~15% de modérateurs
                $role = ConversationParticipant::ROLE_MODERATOR;
            } else {
                $role = ConversationParticipant::ROLE_MEMBER;
            }
            
            $participant->setRole($role);
            
            // On ne fixe pas encore la dernière lecture
            $manager->persist($participant);
            
            $participantEntities[] = $participant;
        }

        // Création de messages dans cette conversation
        $messageCount = mt_rand(20, 40); // Beaucoup de messages pour la conversation publique
        $messageDates = $this->createMessages($manager, $conversation, $users, $messageCount);
        
        // Distribution des dates de dernière lecture pour assurer des messages non lus
        foreach ($participantEntities as $index => $entity) {
            // Différentes positions de lecture selon l'utilisateur pour créer de la variété
            $readPosition = ($index % 5); // 0, 1, 2, 3, 4
            
            switch ($readPosition) {
                case 0: // N'a lu que 20% des messages
                    $readIndex = (int)($messageCount * 0.2);
                    break;
                case 1: // A lu 40% des messages
                    $readIndex = (int)($messageCount * 0.4);
                    break;
                case 2: // A lu 60% des messages
                    $readIndex = (int)($messageCount * 0.6);
                    break;
                case 3: // A lu 75% des messages
                    $readIndex = (int)($messageCount * 0.75);
                    break;
                case 4: // A lu 90% des messages
                    $readIndex = (int)($messageCount * 0.9);
                    break;
            }
            
            if ($readIndex >= count($messageDates)) {
                $readIndex = count($messageDates) - 1;
            }
            
            $entity->setLastReadAt($messageDates[$readIndex]);
        }
    }

    /**
     * Crée des messages dans une conversation
     * @return array Tableau des dates des messages (du plus ancien au plus récent)
     */
    private function createMessages(ObjectManager $manager, Conversation $conversation, array $participants, int $count): array
    {
        // Commencer à la date de création de la conversation
        $messageDate = clone $conversation->getCreatedAt();
        $messageDates = []; // Pour stocker toutes les dates des messages
        
        for ($i = 0; $i < $count; $i++) {
            $authorIndex = array_rand($participants);
            $author = $participants[$authorIndex];
            
            $message = new Message();
            $message->setConversation($conversation);
            $message->setAuthor($author);
            $message->setContent($this->messageContents[array_rand($this->messageContents)]);
            
            // Incrémenter la date pour avoir une chronologie (entre 1 heure et 2 jours plus tard)
            $interval = new \DateInterval('PT' . mt_rand(1, 48) . 'H' . mt_rand(1, 59) . 'M');
            $messageDateTime = $messageDate->add($interval);
            
            // Si la date dépasse maintenant, on s'arrête à 'maintenant - 1 heure' au plus récent
            if ($messageDateTime > new \DateTimeImmutable()) {
                $messageDateTime = new \DateTimeImmutable('-' . mt_rand(1, 60) . ' minutes');
            }
            
            $message->setCreatedAt($messageDateTime);
            $messageDates[] = $messageDateTime; // Stocker la date du message
            
            // Mettre à jour la date pour le prochain message
            $messageDate = $messageDateTime;
            
            // 10% de chance d'avoir un message édité
            if (mt_rand(1, 10) === 1) {
                $message->setIsEdited(true);
                $editDateTime = clone $messageDateTime;
                $editInterval = new \DateInterval('PT' . mt_rand(5, 60) . 'M');
                $editDateTime = $editDateTime->add($editInterval);
                $message->setUpdatedAt($editDateTime);
            }
            
            // 5% de chance d'avoir un message supprimé
            if (mt_rand(1, 20) === 1) {
                $message->setIsDeleted(true);
                $message->setContent(Message::CONTENT_PLACEHOLDER_USER_DELETED);
            }

            $manager->persist($message);
            
            // Mettre à jour la date de la dernière mise à jour de la conversation
            if ($messageDateTime > $conversation->getUpdatedAt()) {
                $conversation->setUpdatedAt($messageDateTime);
            }
        }
        
        return $messageDates;
    }
} 