<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250808221640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attachment (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', widget_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', url VARCHAR(255) NOT NULL, position INT NOT NULL, INDEX IDX_795FD9BBFBE885E2 (widget_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBFBE885E2 FOREIGN KEY (widget_id) REFERENCES widget (id)');
        $this->addSql('ALTER TABLE widget DROP logos');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBFBE885E2');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('ALTER TABLE widget ADD logos JSON NOT NULL');
    }
}
