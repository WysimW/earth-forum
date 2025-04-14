<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414132905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE post_npcs (post_id INT NOT NULL, npc_id INT NOT NULL, INDEX IDX_E0C548954B89032C (post_id), INDEX IDX_E0C54895CA7D6B89 (npc_id), PRIMARY KEY(post_id, npc_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE post_npcs ADD CONSTRAINT FK_E0C548954B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_npcs ADD CONSTRAINT FK_E0C54895CA7D6B89 FOREIGN KEY (npc_id) REFERENCES `npc` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post_npcs DROP FOREIGN KEY FK_E0C548954B89032C');
        $this->addSql('ALTER TABLE post_npcs DROP FOREIGN KEY FK_E0C54895CA7D6B89');
        $this->addSql('DROP TABLE post_npcs');
    }
}
