<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414084646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post ADD quoted_post_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D799F14CF FOREIGN KEY (quoted_post_id) REFERENCES post (id)');
        $this->addSql('CREATE INDEX IDX_5A8A6C8D799F14CF ON post (quoted_post_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D799F14CF');
        $this->addSql('DROP INDEX IDX_5A8A6C8D799F14CF ON post');
        $this->addSql('ALTER TABLE post DROP quoted_post_id');
    }
}
