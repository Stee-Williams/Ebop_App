<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add province_id and type to poste_comptable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poste_comptable ADD type VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE poste_comptable ADD province_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE poste_comptable ADD CONSTRAINT FK_POSTE_COMP_PROVINCE FOREIGN KEY (province_id) REFERENCES province (id)');
        $this->addSql('CREATE INDEX IDX_POSTE_COMP_PROVINCE ON poste_comptable (province_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poste_comptable DROP CONSTRAINT FK_POSTE_COMP_PROVINCE');
        $this->addSql('DROP INDEX IDX_POSTE_COMP_PROVINCE');
        $this->addSql('ALTER TABLE poste_comptable DROP province_id');
        $this->addSql('ALTER TABLE poste_comptable DROP type');
    }
}
