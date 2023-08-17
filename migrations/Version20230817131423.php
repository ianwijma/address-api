<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230817131423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address ADD country VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_address ON address (country, region, district, postcode, street, number, unit)');
        $this->addSql('CREATE UNIQUE INDEX unique_hash ON address (hash)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX unique_address');
        $this->addSql('DROP INDEX unique_hash');
        $this->addSql('ALTER TABLE address DROP country');
    }
}
