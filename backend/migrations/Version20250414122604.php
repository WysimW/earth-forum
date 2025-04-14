<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414122604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `npc` (id INT AUTO_INCREMENT NOT NULL, universe_id INT DEFAULT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, pseudonyms LONGTEXT DEFAULT NULL, gender VARCHAR(20) DEFAULT NULL, moral_affiliation VARCHAR(50) DEFAULT NULL, factions LONGTEXT DEFAULT NULL, occupation VARCHAR(255) DEFAULT NULL, equipment LONGTEXT DEFAULT NULL, weaknesses LONGTEXT DEFAULT NULL, age VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, biography LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, personality LONGTEXT DEFAULT NULL, appearance LONGTEXT DEFAULT NULL, abilities LONGTEXT DEFAULT NULL, validated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status_message LONGTEXT DEFAULT NULL, moderation_note LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, role_in_story LONGTEXT DEFAULT NULL, relationships LONGTEXT DEFAULT NULL, quests LONGTEXT DEFAULT NULL, dialogue_style LONGTEXT DEFAULT NULL, secrets LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_468C762C5CD9AF2 (universe_id), INDEX IDX_468C762CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `npc` ADD CONSTRAINT FK_468C762C5CD9AF2 FOREIGN KEY (universe_id) REFERENCES univers (id)');
        $this->addSql('ALTER TABLE `npc` ADD CONSTRAINT FK_468C762CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `npc` DROP FOREIGN KEY FK_468C762C5CD9AF2');
        $this->addSql('ALTER TABLE `npc` DROP FOREIGN KEY FK_468C762CA76ED395');
        $this->addSql('DROP TABLE `npc`');
    }
}
