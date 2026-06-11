<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le titre aux engagements et des lignes budgétaires pour les budgets vides';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE engagement ADD titre VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE engagement SET titre = numero WHERE titre IS NULL');

        $this->addSql(
            "SELECT setval(
                pg_get_serial_sequence('ligne_budgetaire', 'id'),
                COALESCE((SELECT MAX(id) FROM ligne_budgetaire), 1)
            )"
        );

        $this->addSql(<<<'SQL'
            INSERT INTO ligne_budgetaire (code, libelle, montant_alloue, montant_utilise, budget_id)
            SELECT
                'LB-' || b.id || '-01',
                'Fonctionnement courant',
                ROUND(b.montant * 0.40, 2),
                0,
                b.id
            FROM budget b
            WHERE NOT EXISTS (
                SELECT 1 FROM ligne_budgetaire lb WHERE lb.budget_id = b.id
            )
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO ligne_budgetaire (code, libelle, montant_alloue, montant_utilise, budget_id)
            SELECT
                'LB-' || b.id || '-02',
                'Investissements et équipements',
                ROUND(b.montant * 0.35, 2),
                0,
                b.id
            FROM budget b
            WHERE (SELECT COUNT(*) FROM ligne_budgetaire lb WHERE lb.budget_id = b.id) = 1
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO ligne_budgetaire (code, libelle, montant_alloue, montant_utilise, budget_id)
            SELECT
                'LB-' || b.id || '-03',
                'Prestations et services extérieurs',
                ROUND(b.montant * 0.25, 2),
                0,
                b.id
            FROM budget b
            WHERE (SELECT COUNT(*) FROM ligne_budgetaire lb WHERE lb.budget_id = b.id) = 2
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE engagement DROP titre');
    }
}
