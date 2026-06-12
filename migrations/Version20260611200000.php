<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute budget_decaisse sur engagement pour le suivi des décaissements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE engagement ADD budget_decaisse BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE engagement DROP budget_decaisse');
    }
}
