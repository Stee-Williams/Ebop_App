<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517231348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE engagement ADD numero VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE engagement DROP id_engagement');
        $this->addSql('ALTER TABLE engagement ALTER montant TYPE NUMERIC(12, 2)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D86F0141F55AE19E ON engagement (numero)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D86F0141F55AE19E');
        $this->addSql('ALTER TABLE engagement ADD id_engagement INT NOT NULL');
        $this->addSql('ALTER TABLE engagement DROP numero');
        $this->addSql('ALTER TABLE engagement ALTER montant TYPE NUMERIC(10, 0)');
    }
}
