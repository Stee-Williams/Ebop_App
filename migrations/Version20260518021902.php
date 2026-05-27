<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518021902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE budget ADD unite_operationnelle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT FK_73F2F77BD86F2C84 FOREIGN KEY (unite_operationnelle_id) REFERENCES unite_operationnelle (id)');
        $this->addSql('CREATE INDEX IDX_73F2F77BD86F2C84 ON budget (unite_operationnelle_id)');
        $this->addSql('ALTER TABLE engagement ADD ligne_budgetaire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE engagement ADD poste_comptable_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE engagement ADD fournisseur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE engagement ADD users_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE engagement ADD CONSTRAINT FK_D86F01412F59CE5F FOREIGN KEY (ligne_budgetaire_id) REFERENCES ligne_budgetaire (id)');
        $this->addSql('ALTER TABLE engagement ADD CONSTRAINT FK_D86F01418699E3DC FOREIGN KEY (poste_comptable_id) REFERENCES poste_comptable (id)');
        $this->addSql('ALTER TABLE engagement ADD CONSTRAINT FK_D86F0141670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE engagement ADD CONSTRAINT FK_D86F014167B3B43D FOREIGN KEY (users_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_D86F01412F59CE5F ON engagement (ligne_budgetaire_id)');
        $this->addSql('CREATE INDEX IDX_D86F01418699E3DC ON engagement (poste_comptable_id)');
        $this->addSql('CREATE INDEX IDX_D86F0141670C757F ON engagement (fournisseur_id)');
        $this->addSql('CREATE INDEX IDX_D86F014167B3B43D ON engagement (users_id)');
        $this->addSql('ALTER TABLE ligne_budgetaire ADD budget_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_budgetaire ADD CONSTRAINT FK_C5963E1A36ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id)');
        $this->addSql('CREATE INDEX IDX_C5963E1A36ABA6B8 ON ligne_budgetaire (budget_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE budget DROP CONSTRAINT FK_73F2F77BD86F2C84');
        $this->addSql('DROP INDEX IDX_73F2F77BD86F2C84');
        $this->addSql('ALTER TABLE budget DROP unite_operationnelle_id');
        $this->addSql('ALTER TABLE engagement DROP CONSTRAINT FK_D86F01412F59CE5F');
        $this->addSql('ALTER TABLE engagement DROP CONSTRAINT FK_D86F01418699E3DC');
        $this->addSql('ALTER TABLE engagement DROP CONSTRAINT FK_D86F0141670C757F');
        $this->addSql('ALTER TABLE engagement DROP CONSTRAINT FK_D86F014167B3B43D');
        $this->addSql('DROP INDEX IDX_D86F01412F59CE5F');
        $this->addSql('DROP INDEX IDX_D86F01418699E3DC');
        $this->addSql('DROP INDEX IDX_D86F0141670C757F');
        $this->addSql('DROP INDEX IDX_D86F014167B3B43D');
        $this->addSql('ALTER TABLE engagement DROP ligne_budgetaire_id');
        $this->addSql('ALTER TABLE engagement DROP poste_comptable_id');
        $this->addSql('ALTER TABLE engagement DROP fournisseur_id');
        $this->addSql('ALTER TABLE engagement DROP users_id');
        $this->addSql('ALTER TABLE ligne_budgetaire DROP CONSTRAINT FK_C5963E1A36ABA6B8');
        $this->addSql('DROP INDEX IDX_C5963E1A36ABA6B8');
        $this->addSql('ALTER TABLE ligne_budgetaire DROP budget_id');
    }
}
