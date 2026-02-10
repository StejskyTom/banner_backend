<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204131344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_widget ADD divider_enabled TINYINT(1) DEFAULT NULL, ADD divider_color VARCHAR(20) DEFAULT NULL, ADD divider_width INT DEFAULT NULL, ADD divider_height INT DEFAULT NULL, ADD divider_style VARCHAR(20) DEFAULT NULL, ADD divider_margin INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_widget DROP divider_enabled, DROP divider_color, DROP divider_width, DROP divider_height, DROP divider_style, DROP divider_margin');
    }
}
