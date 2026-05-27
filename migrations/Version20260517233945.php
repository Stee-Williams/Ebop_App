<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517233945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administration ADD nom VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE administration ADD code VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE administration DROP nom_adm');
        $this->addSql('ALTER TABLE administration DROP code_adm');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9FDD0D1877153098 ON administration (code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_9FDD0D1877153098');
        $this->addSql('ALTER TABLE administration ADD nom_adm VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE administration ADD code_adm INT NOT NULL');
        $this->addSql('ALTER TABLE administration DROP nom');
        $this->addSql('ALTER TABLE administration DROP code');
    }
}
