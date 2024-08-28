<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240828144146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `character` (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, universe_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_937AB034A76ED395 (user_id), INDEX IDX_937AB0345CD9AF2 (universe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forum (id INT AUTO_INCREMENT NOT NULL, universe_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_852BBECD5CD9AF2 (universe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, sub_forum_id INT DEFAULT NULL, author_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5A8A6C8D93635489 (sub_forum_id), INDEX IDX_5A8A6C8DF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sub_forum (id INT AUTO_INCREMENT NOT NULL, forum_id INT DEFAULT NULL, subforum_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, is_rp TINYINT(1) NOT NULL, INDEX IDX_696FDA7429CCBAD0 (forum_id), INDEX IDX_696FDA74225C0759 (subforum_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE universe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB034A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB0345CD9AF2 FOREIGN KEY (universe_id) REFERENCES universe (id)');
        $this->addSql('ALTER TABLE forum ADD CONSTRAINT FK_852BBECD5CD9AF2 FOREIGN KEY (universe_id) REFERENCES universe (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D93635489 FOREIGN KEY (sub_forum_id) REFERENCES sub_forum (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE sub_forum ADD CONSTRAINT FK_696FDA7429CCBAD0 FOREIGN KEY (forum_id) REFERENCES forum (id)');
        $this->addSql('ALTER TABLE sub_forum ADD CONSTRAINT FK_696FDA74225C0759 FOREIGN KEY (subforum_id) REFERENCES sub_forum (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB034A76ED395');
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB0345CD9AF2');
        $this->addSql('ALTER TABLE forum DROP FOREIGN KEY FK_852BBECD5CD9AF2');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D93635489');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DF675F31B');
        $this->addSql('ALTER TABLE sub_forum DROP FOREIGN KEY FK_696FDA7429CCBAD0');
        $this->addSql('ALTER TABLE sub_forum DROP FOREIGN KEY FK_696FDA74225C0759');
        $this->addSql('DROP TABLE `character`');
        $this->addSql('DROP TABLE forum');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE sub_forum');
        $this->addSql('DROP TABLE universe');
        $this->addSql('DROP TABLE `user`');
    }
}
