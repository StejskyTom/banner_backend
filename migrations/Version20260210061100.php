<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210061100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_widget ADD title_tag VARCHAR(10) DEFAULT NULL, ADD title_color VARCHAR(20) DEFAULT NULL, ADD title_size VARCHAR(20) DEFAULT NULL, ADD title_font VARCHAR(100) DEFAULT NULL, ADD title_bold TINYINT(1) DEFAULT NULL, ADD title_italic TINYINT(1) DEFAULT NULL, ADD title_align VARCHAR(20) DEFAULT NULL, ADD title_margin_bottom INT DEFAULT NULL, ADD subtitle_text LONGTEXT DEFAULT NULL, ADD subtitle_tag VARCHAR(10) DEFAULT NULL, ADD subtitle_color VARCHAR(20) DEFAULT NULL, ADD subtitle_size VARCHAR(20) DEFAULT NULL, ADD subtitle_font VARCHAR(100) DEFAULT NULL, ADD subtitle_bold TINYINT(1) DEFAULT NULL, ADD subtitle_italic TINYINT(1) DEFAULT NULL, ADD subtitle_align VARCHAR(20) DEFAULT NULL, ADD subtitle_margin_bottom INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_widget DROP title_tag, DROP title_color, DROP title_size, DROP title_font, DROP title_bold, DROP title_italic, DROP title_align, DROP title_margin_bottom, DROP subtitle_text, DROP subtitle_tag, DROP subtitle_color, DROP subtitle_size, DROP subtitle_font, DROP subtitle_bold, DROP subtitle_italic, DROP subtitle_align, DROP subtitle_margin_bottom');
    }
}
