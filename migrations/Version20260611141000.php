<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611141000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Resynchronise la séquence auto-incrémentée de la table users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "SELECT setval(
                pg_get_serial_sequence('users', 'id'),
                COALESCE((SELECT MAX(id) FROM users), 1)
            )"
        );
    }

    public function down(Schema $schema): void
    {
        // Pas de rollback nécessaire
    }
}
