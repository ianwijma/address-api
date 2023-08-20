<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230820055321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_version');
        $this->addSql('ALTER TABLE version RENAME COLUMN number TO version_number');
        $this->addSql('CREATE UNIQUE INDEX unique_version ON version (version_number)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX unique_version');
        $this->addSql('ALTER TABLE version RENAME COLUMN version_number TO number');
        $this->addSql('CREATE UNIQUE INDEX unique_version ON version (number)');
    }
}
