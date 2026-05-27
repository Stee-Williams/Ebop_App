<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518004513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE province ADD nom VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE province ADD code VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE province DROP name');
        $this->addSql('ALTER TABLE province DROP code_pro');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4ADAD40B77153098 ON province (code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_4ADAD40B77153098');
        $this->addSql('ALTER TABLE province ADD name VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE province ADD code_pro INT NOT NULL');
        $this->addSql('ALTER TABLE province DROP nom');
        $this->addSql('ALTER TABLE province DROP code');
    }
}
