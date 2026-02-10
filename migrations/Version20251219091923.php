<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251219091923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_full_path ON category');
        $this->addSql('ALTER TABLE category CHANGE full_path full_path VARCHAR(750) NOT NULL');
        $this->addSql('CREATE INDEX idx_full_path ON category (full_path)');
        $this->addSql('ALTER TABLE heureka_feed ADD layout VARCHAR(50) DEFAULT \'carousel\' NOT NULL, ADD layout_options JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE heureka_feed DROP layout, DROP layout_options');
        $this->addSql('DROP INDEX idx_full_path ON category');
        $this->addSql('ALTER TABLE category CHANGE full_path full_path VARCHAR(1000) NOT NULL');
        $this->addSql('CREATE INDEX idx_full_path ON category (full_path(255))');
    }
}
