<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230817123212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE address (id UUID NOT NULL, coordinate_id UUID DEFAULT NULL, number VARCHAR(255) DEFAULT NULL, street VARCHAR(255) DEFAULT NULL, unit VARCHAR(255) DEFAULT NULL, district VARCHAR(255) DEFAULT NULL, region VARCHAR(255) DEFAULT NULL, postcode VARCHAR(255) DEFAULT NULL, hash VARCHAR(255) DEFAULT NULL, external_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D4E6F8198BBE953 ON address (coordinate_id)');
        $this->addSql('COMMENT ON COLUMN address.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN address.coordinate_id IS \'(DC2Type:ulid)\'');
        $this->addSql('CREATE TABLE coordinate (id UUID NOT NULL, north NUMERIC(7, 0) NOT NULL, east NUMERIC(7, 0) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN coordinate.id IS \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F8198BBE953 FOREIGN KEY (coordinate_id) REFERENCES coordinate (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE address DROP CONSTRAINT FK_D4E6F8198BBE953');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE coordinate');
    }
}
