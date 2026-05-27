<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517235054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE budget ADD libelle VARCHAR(150) NOT NULL');
        $this->addSql('ALTER TABLE budget DROP "année"');
        $this->addSql('ALTER TABLE budget DROP "libellé"');
        $this->addSql('ALTER TABLE budget ALTER montant TYPE NUMERIC(15, 2)');
        $this->addSql('ALTER TABLE budget RENAME COLUMN id_budget TO annee');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE budget ADD "année" DATE NOT NULL');
        $this->addSql('ALTER TABLE budget ADD "libellé" VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE budget DROP libelle');
        $this->addSql('ALTER TABLE budget ALTER montant TYPE NUMERIC(10, 0)');
        $this->addSql('ALTER TABLE budget RENAME COLUMN annee TO id_budget');
    }
}
