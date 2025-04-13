<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250413090535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `character` ADD actual_pseudo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE post ADD is_draft TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE thread ADD first_post_content LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `character` DROP actual_pseudo');
        $this->addSql('ALTER TABLE post DROP is_draft');
        $this->addSql('ALTER TABLE thread DROP first_post_content');
    }
}
