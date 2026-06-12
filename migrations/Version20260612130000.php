<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rétablit montant_alloue : le décaissement ne doit plus le diminuer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            UPDATE ligne_budgetaire
            SET montant_alloue = montant_alloue::numeric + montant_decaisse::numeric
            WHERE montant_decaisse::numeric > 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            UPDATE ligne_budgetaire
            SET montant_alloue = GREATEST(0, montant_alloue::numeric - montant_decaisse::numeric)
            WHERE montant_decaisse::numeric > 0
        ');
    }
}
