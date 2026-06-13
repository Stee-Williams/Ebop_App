<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bank transfer fields to reglement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reglement ADD numero_compte VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE reglement ADD banque_fournisseur VARCHAR(150) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reglement DROP numero_compte');
        $this->addSql('ALTER TABLE reglement DROP banque_fournisseur');
    }
}
