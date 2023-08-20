<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230820000904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address ADD version INT NOT NULL');
        $this->addSql('DROP INDEX unique_coordinate');
        $this->addSql('ALTER TABLE coordinate ADD version INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_coordinate ON coordinate (north, east, version)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX unique_coordinate');
        $this->addSql('ALTER TABLE coordinate DROP version');
        $this->addSql('CREATE UNIQUE INDEX unique_coordinate ON coordinate (north, east)');
        $this->addSql('ALTER TABLE address DROP version');
    }
}
