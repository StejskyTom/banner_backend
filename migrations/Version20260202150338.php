<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202150338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_widget ADD question_tag VARCHAR(10) DEFAULT NULL, ADD question_size VARCHAR(20) DEFAULT NULL, ADD question_font VARCHAR(100) DEFAULT NULL, ADD question_bold TINYINT(1) DEFAULT NULL, ADD question_italic TINYINT(1) DEFAULT NULL, ADD question_align VARCHAR(20) DEFAULT NULL, ADD question_margin_bottom INT DEFAULT NULL, ADD answer_tag VARCHAR(10) DEFAULT NULL, ADD answer_size VARCHAR(20) DEFAULT NULL, ADD answer_font VARCHAR(100) DEFAULT NULL, ADD answer_bold TINYINT(1) DEFAULT NULL, ADD answer_italic TINYINT(1) DEFAULT NULL, ADD answer_align VARCHAR(20) DEFAULT NULL, ADD answer_margin_bottom INT DEFAULT NULL, ADD arrow_position VARCHAR(20) DEFAULT NULL, ADD arrow_color VARCHAR(20) DEFAULT NULL, ADD arrow_size INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_widget DROP question_tag, DROP question_size, DROP question_font, DROP question_bold, DROP question_italic, DROP question_align, DROP question_margin_bottom, DROP answer_tag, DROP answer_size, DROP answer_font, DROP answer_bold, DROP answer_italic, DROP answer_align, DROP answer_margin_bottom, DROP arrow_position, DROP arrow_color, DROP arrow_size');
    }
}
