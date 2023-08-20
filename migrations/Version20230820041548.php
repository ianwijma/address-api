<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230820041548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE version (id UUID NOT NULL, number INT NOT NULL, active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX unique_version ON version (number)');
        $this->addSql('COMMENT ON COLUMN version.id IS \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE address ADD version_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE address DROP version');
        $this->addSql('COMMENT ON COLUMN address.version_id IS \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F814BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D4E6F814BBC2705 ON address (version_id)');
        $this->addSql('DROP INDEX unique_coordinate');
        $this->addSql('ALTER TABLE coordinate ADD version_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE coordinate DROP version');
        $this->addSql('COMMENT ON COLUMN coordinate.version_id IS \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE coordinate ADD CONSTRAINT FK_CB9CBA174BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_CB9CBA174BBC2705 ON coordinate (version_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_coordinate ON coordinate (north, east, version_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE address DROP CONSTRAINT FK_D4E6F814BBC2705');
        $this->addSql('ALTER TABLE coordinate DROP CONSTRAINT FK_CB9CBA174BBC2705');
        $this->addSql('DROP TABLE version');
        $this->addSql('DROP INDEX IDX_CB9CBA174BBC2705');
        $this->addSql('DROP INDEX unique_coordinate');
        $this->addSql('ALTER TABLE coordinate ADD version INT NOT NULL');
        $this->addSql('ALTER TABLE coordinate DROP version_id');
        $this->addSql('CREATE UNIQUE INDEX unique_coordinate ON coordinate (north, east, version)');
        $this->addSql('DROP INDEX IDX_D4E6F814BBC2705');
        $this->addSql('ALTER TABLE address ADD version INT NOT NULL');
        $this->addSql('ALTER TABLE address DROP version_id');
    }
}
