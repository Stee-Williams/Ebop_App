<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute montant_decaisse sur ligne_budgetaire pour le suivi des règlements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ligne_budgetaire ADD montant_decaisse NUMERIC(12, 2) DEFAULT 0 NOT NULL');

        $this->addSql('
            UPDATE ligne_budgetaire l
            SET montant_decaisse = COALESCE((
                SELECT SUM(e.montant::numeric)
                FROM engagement e
                WHERE e.ligne_budgetaire_id = l.id
                  AND e.budget_decaisse = true
            ), 0)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ligne_budgetaire DROP montant_decaisse');
    }
}
