<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260103223534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD invoice_number VARCHAR(255) DEFAULT NULL, ADD billing_snapshot JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD billing_name VARCHAR(255) DEFAULT NULL, ADD billing_ico VARCHAR(20) DEFAULT NULL, ADD billing_dic VARCHAR(20) DEFAULT NULL, ADD billing_street VARCHAR(255) DEFAULT NULL, ADD billing_city VARCHAR(255) DEFAULT NULL, ADD billing_zip VARCHAR(20) DEFAULT NULL, ADD billing_country VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP invoice_number, DROP billing_snapshot');
        $this->addSql('ALTER TABLE user DROP billing_name, DROP billing_ico, DROP billing_dic, DROP billing_street, DROP billing_city, DROP billing_zip, DROP billing_country');
    }
}
