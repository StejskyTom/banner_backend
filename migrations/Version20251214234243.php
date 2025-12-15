<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251214234243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', parent_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, full_path VARCHAR(1000) NOT NULL, product_count INT DEFAULT 0 NOT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), INDEX idx_full_path (full_path(255)), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE heureka_feed (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, url VARCHAR(500) NOT NULL, last_synced_at DATETIME DEFAULT NULL, product_count INT DEFAULT 0 NOT NULL, INDEX IDX_D068EAFEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', feed_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', category_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', item_id VARCHAR(255) NOT NULL, product_name VARCHAR(500) NOT NULL, description LONGTEXT DEFAULT NULL, price_vat NUMERIC(10, 2) NOT NULL, url VARCHAR(1000) NOT NULL, img_url VARCHAR(1000) DEFAULT NULL, img_url_alternative VARCHAR(1000) DEFAULT NULL, manufacturer VARCHAR(255) DEFAULT NULL, ean VARCHAR(100) DEFAULT NULL, product_no VARCHAR(100) DEFAULT NULL, is_selected TINYINT(1) DEFAULT 0 NOT NULL, position INT DEFAULT 0 NOT NULL, INDEX IDX_D34A04AD51A5BC03 (feed_id), INDEX IDX_D34A04AD12469DE2 (category_id), INDEX idx_item_id (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE heureka_feed ADD CONSTRAINT FK_D068EAFEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD51A5BC03 FOREIGN KEY (feed_id) REFERENCES heureka_feed (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE heureka_feed DROP FOREIGN KEY FK_D068EAFEA76ED395');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD51A5BC03');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE heureka_feed');
        $this->addSql('DROP TABLE product');
    }
}
