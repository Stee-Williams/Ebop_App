<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le rôle SUPER_ADMIN';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "SELECT setval(
                pg_get_serial_sequence('role', 'id'),
                COALESCE((SELECT MAX(id) FROM role), 1)
            )"
        );

        $this->addSql(
            "INSERT INTO role (nom)
             SELECT 'SUPER_ADMIN'
             WHERE NOT EXISTS (SELECT 1 FROM role WHERE nom = 'SUPER_ADMIN')"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role WHERE nom = 'SUPER_ADMIN'");
    }
}
