<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250415104849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE elseworld (id INT AUTO_INCREMENT NOT NULL, parent_universe_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, banner VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_E55D7B0989D9B62 (slug), INDEX IDX_E55D7B06EE18882 (parent_universe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE elseworld ADD CONSTRAINT FK_E55D7B06EE18882 FOREIGN KEY (parent_universe_id) REFERENCES univers (id)');
        $this->addSql('ALTER TABLE `character` ADD elseworld_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB0347667CD8D FOREIGN KEY (elseworld_id) REFERENCES elseworld (id)');
        $this->addSql('CREATE INDEX IDX_937AB0347667CD8D ON `character` (elseworld_id)');
        $this->addSql('ALTER TABLE forum ADD elseworld_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE forum ADD CONSTRAINT FK_852BBECD7667CD8D FOREIGN KEY (elseworld_id) REFERENCES elseworld (id)');
        $this->addSql('CREATE INDEX IDX_852BBECD7667CD8D ON forum (elseworld_id)');
        $this->addSql('ALTER TABLE npc ADD elseworld_id INT DEFAULT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE npc ADD CONSTRAINT FK_468C762C7667CD8D FOREIGN KEY (elseworld_id) REFERENCES elseworld (id)');
        $this->addSql('CREATE INDEX IDX_468C762C7667CD8D ON npc (elseworld_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB0347667CD8D');
        $this->addSql('ALTER TABLE forum DROP FOREIGN KEY FK_852BBECD7667CD8D');
        $this->addSql('ALTER TABLE `npc` DROP FOREIGN KEY FK_468C762C7667CD8D');
        $this->addSql('ALTER TABLE elseworld DROP FOREIGN KEY FK_E55D7B06EE18882');
        $this->addSql('DROP TABLE elseworld');
        $this->addSql('DROP INDEX IDX_937AB0347667CD8D ON `character`');
        $this->addSql('ALTER TABLE `character` DROP elseworld_id');
        $this->addSql('DROP INDEX IDX_852BBECD7667CD8D ON forum');
        $this->addSql('ALTER TABLE forum DROP elseworld_id');
        $this->addSql('DROP INDEX IDX_468C762C7667CD8D ON `npc`');
        $this->addSql('ALTER TABLE `npc` DROP elseworld_id, CHANGE user_id user_id INT DEFAULT NULL');
    }
}
