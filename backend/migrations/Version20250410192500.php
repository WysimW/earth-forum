<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410192500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `character` (id INT AUTO_INCREMENT NOT NULL, universe_id INT DEFAULT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, biography LONGTEXT DEFAULT NULL, alias VARCHAR(50) DEFAULT NULL, status VARCHAR(20) NOT NULL, personality LONGTEXT DEFAULT NULL, appearance LONGTEXT DEFAULT NULL, abilities LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', validated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status_message LONGTEXT DEFAULT NULL, moderation_note LONGTEXT DEFAULT NULL, INDEX IDX_937AB0345CD9AF2 (universe_id), INDEX IDX_937AB034A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE character_relation (id INT AUTO_INCREMENT NOT NULL, source_character_id INT NOT NULL, target_character_id INT NOT NULL, type VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3A88AED8C7DC62CD (source_character_id), INDEX IDX_3A88AED8F1646D24 (target_character_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forum (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, universe_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, banner VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, hero_logo VARCHAR(255) DEFAULT NULL, is_roleplay TINYINT(1) DEFAULT 0 NOT NULL, status VARCHAR(20) NOT NULL, position INT NOT NULL, slug VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_852BBECD989D9B62 (slug), INDEX IDX_852BBECD12469DE2 (category_id), INDEX IDX_852BBECD5CD9AF2 (universe_id), INDEX IDX_852BBECD727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forum_category (id INT AUTO_INCREMENT NOT NULL, type_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, home_order INT DEFAULT NULL, UNIQUE INDEX UNIQ_21BF9426989D9B62 (slug), INDEX IDX_21BF9426C54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, universe_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, is_public TINYINT(1) DEFAULT NULL, INDEX IDX_5E9E89CB5CD9AF2 (universe_id), INDEX IDX_5E9E89CB727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, thread_id INT NOT NULL, author_id INT DEFAULT NULL, character_id INT DEFAULT NULL, content LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, edited_at DATETIME DEFAULT NULL, type VARCHAR(20) NOT NULL, post_type VARCHAR(20) DEFAULT NULL, INDEX IDX_5A8A6C8DE2904019 (thread_id), INDEX IDX_5A8A6C8DF675F31B (author_id), INDEX IDX_5A8A6C8D1136BE75 (character_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(7) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE thread (id INT AUTO_INCREMENT NOT NULL, forum_id INT NOT NULL, author_id INT DEFAULT NULL, character_creator_id INT DEFAULT NULL, character_sheet_id INT DEFAULT NULL, location_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, tags VARCHAR(255) DEFAULT NULL, max_participants INT DEFAULT NULL, sticky TINYINT(1) DEFAULT NULL, visible_to_characters_only TINYINT(1) DEFAULT 0 NOT NULL, slug VARCHAR(255) NOT NULL, INDEX IDX_31204C8329CCBAD0 (forum_id), INDEX IDX_31204C83F675F31B (author_id), INDEX IDX_31204C83D0AEB720 (character_creator_id), INDEX IDX_31204C83D313EF34 (character_sheet_id), INDEX IDX_31204C8364D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE thread_participants (thread_id INT NOT NULL, character_id INT NOT NULL, INDEX IDX_256C742E2904019 (thread_id), INDEX IDX_256C7421136BE75 (character_id), PRIMARY KEY(thread_id, character_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE univers (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, pseudo VARCHAR(255) NOT NULL, avatar VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_login DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_role (user_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_2DE8C6A3A76ED395 (user_id), INDEX IDX_2DE8C6A3D60322AC (role_id), PRIMARY KEY(user_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB0345CD9AF2 FOREIGN KEY (universe_id) REFERENCES univers (id)');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB034A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE character_relation ADD CONSTRAINT FK_3A88AED8C7DC62CD FOREIGN KEY (source_character_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE character_relation ADD CONSTRAINT FK_3A88AED8F1646D24 FOREIGN KEY (target_character_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE forum ADD CONSTRAINT FK_852BBECD12469DE2 FOREIGN KEY (category_id) REFERENCES forum_category (id)');
        $this->addSql('ALTER TABLE forum ADD CONSTRAINT FK_852BBECD5CD9AF2 FOREIGN KEY (universe_id) REFERENCES univers (id)');
        $this->addSql('ALTER TABLE forum ADD CONSTRAINT FK_852BBECD727ACA70 FOREIGN KEY (parent_id) REFERENCES forum (id)');
        $this->addSql('ALTER TABLE forum_category ADD CONSTRAINT FK_21BF9426C54C8C93 FOREIGN KEY (type_id) REFERENCES categories_type (id)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB5CD9AF2 FOREIGN KEY (universe_id) REFERENCES univers (id)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB727ACA70 FOREIGN KEY (parent_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DE2904019 FOREIGN KEY (thread_id) REFERENCES thread (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D1136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE thread ADD CONSTRAINT FK_31204C8329CCBAD0 FOREIGN KEY (forum_id) REFERENCES forum (id)');
        $this->addSql('ALTER TABLE thread ADD CONSTRAINT FK_31204C83F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE thread ADD CONSTRAINT FK_31204C83D0AEB720 FOREIGN KEY (character_creator_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE thread ADD CONSTRAINT FK_31204C83D313EF34 FOREIGN KEY (character_sheet_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE thread ADD CONSTRAINT FK_31204C8364D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE thread_participants ADD CONSTRAINT FK_256C742E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE thread_participants ADD CONSTRAINT FK_256C7421136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB0345CD9AF2');
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB034A76ED395');
        $this->addSql('ALTER TABLE character_relation DROP FOREIGN KEY FK_3A88AED8C7DC62CD');
        $this->addSql('ALTER TABLE character_relation DROP FOREIGN KEY FK_3A88AED8F1646D24');
        $this->addSql('ALTER TABLE forum DROP FOREIGN KEY FK_852BBECD12469DE2');
        $this->addSql('ALTER TABLE forum DROP FOREIGN KEY FK_852BBECD5CD9AF2');
        $this->addSql('ALTER TABLE forum DROP FOREIGN KEY FK_852BBECD727ACA70');
        $this->addSql('ALTER TABLE forum_category DROP FOREIGN KEY FK_21BF9426C54C8C93');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB5CD9AF2');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB727ACA70');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DE2904019');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DF675F31B');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D1136BE75');
        $this->addSql('ALTER TABLE thread DROP FOREIGN KEY FK_31204C8329CCBAD0');
        $this->addSql('ALTER TABLE thread DROP FOREIGN KEY FK_31204C83F675F31B');
        $this->addSql('ALTER TABLE thread DROP FOREIGN KEY FK_31204C83D0AEB720');
        $this->addSql('ALTER TABLE thread DROP FOREIGN KEY FK_31204C83D313EF34');
        $this->addSql('ALTER TABLE thread DROP FOREIGN KEY FK_31204C8364D218E');
        $this->addSql('ALTER TABLE thread_participants DROP FOREIGN KEY FK_256C742E2904019');
        $this->addSql('ALTER TABLE thread_participants DROP FOREIGN KEY FK_256C7421136BE75');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3A76ED395');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3D60322AC');
        $this->addSql('DROP TABLE categories_type');
        $this->addSql('DROP TABLE `character`');
        $this->addSql('DROP TABLE character_relation');
        $this->addSql('DROP TABLE forum');
        $this->addSql('DROP TABLE forum_category');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE thread');
        $this->addSql('DROP TABLE thread_participants');
        $this->addSql('DROP TABLE univers');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_role');
    }
}
