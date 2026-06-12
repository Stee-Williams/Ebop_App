<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'API tokens, champs visa engagement, table reglement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_token (id SERIAL NOT NULL, user_id INT NOT NULL, token VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7BA2F5E15F37A13B ON api_token (token)');
        $this->addSql('CREATE INDEX IDX_7BA2F5E1A76ED395 ON api_token (user_id)');
        $this->addSql('ALTER TABLE api_token ADD CONSTRAINT FK_7BA2F5E1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE engagement ADD vise_par_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE engagement ADD date_visa TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE engagement ADD motif_rejet TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE engagement ADD budget_engage BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE engagement ADD CONSTRAINT FK_226DDC7B5A0D523 FOREIGN KEY (vise_par_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_226DDC7B5A0D523 ON engagement (vise_par_id)');

        $this->addSql('CREATE TABLE reglement (id SERIAL NOT NULL, engagement_id INT NOT NULL, cree_par_id INT NOT NULL, reference VARCHAR(50) NOT NULL, montant NUMERIC(12, 2) NOT NULL, mode_paiement VARCHAR(50) NOT NULL, date_reglement DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C4F2F3BAEA34913 ON reglement (reference)');
        $this->addSql('CREATE INDEX IDX_9C4F2F3B4C2F5C47 ON reglement (engagement_id)');
        $this->addSql('CREATE INDEX IDX_9C4F2F3BFC29A3F8 ON reglement (cree_par_id)');
        $this->addSql('ALTER TABLE reglement ADD CONSTRAINT FK_9C4F2F3B4C2F5C47 FOREIGN KEY (engagement_id) REFERENCES engagement (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reglement ADD CONSTRAINT FK_9C4F2F3BFC29A3F8 FOREIGN KEY (cree_par_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_token DROP CONSTRAINT FK_7BA2F5E1A76ED395');
        $this->addSql('DROP TABLE api_token');

        $this->addSql('ALTER TABLE engagement DROP CONSTRAINT FK_226DDC7B5A0D523');
        $this->addSql('DROP INDEX IDX_226DDC7B5A0D523');
        $this->addSql('ALTER TABLE engagement DROP vise_par_id');
        $this->addSql('ALTER TABLE engagement DROP date_visa');
        $this->addSql('ALTER TABLE engagement DROP motif_rejet');
        $this->addSql('ALTER TABLE engagement DROP budget_engage');

        $this->addSql('ALTER TABLE reglement DROP CONSTRAINT FK_9C4F2F3B4C2F5C47');
        $this->addSql('ALTER TABLE reglement DROP CONSTRAINT FK_9C4F2F3BFC29A3F8');
        $this->addSql('DROP TABLE reglement');
    }
}
