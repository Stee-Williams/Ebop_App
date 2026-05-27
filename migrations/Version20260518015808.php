<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518015808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE unite_operationnelle ADD administration_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE unite_operationnelle ADD CONSTRAINT FK_7F7A3C4A39B8E743 FOREIGN KEY (administration_id) REFERENCES administration (id)');
        $this->addSql('CREATE INDEX IDX_7F7A3C4A39B8E743 ON unite_operationnelle (administration_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE unite_operationnelle DROP CONSTRAINT FK_7F7A3C4A39B8E743');
        $this->addSql('DROP INDEX IDX_7F7A3C4A39B8E743');
        $this->addSql('ALTER TABLE unite_operationnelle DROP administration_id');
    }
}
