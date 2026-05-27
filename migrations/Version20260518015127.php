<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518015127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administration ADD province_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE administration ADD CONSTRAINT FK_9FDD0D18E946114A FOREIGN KEY (province_id) REFERENCES province (id)');
        $this->addSql('CREATE INDEX IDX_9FDD0D18E946114A ON administration (province_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administration DROP CONSTRAINT FK_9FDD0D18E946114A');
        $this->addSql('DROP INDEX IDX_9FDD0D18E946114A');
        $this->addSql('ALTER TABLE administration DROP province_id');
    }
}
