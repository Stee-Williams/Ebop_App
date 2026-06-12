<?php

namespace App\Service;

use App\Entity\Engagement;
use App\Entity\User;

final class EngagementWorkflowService
{
    public function __construct(
        private readonly BudgetLigneService $budgetLigneService,
    ) {
    }

    public function onCreate(Engagement $engagement): void
    {
        $this->ensureBudgetEngaged($engagement);
    }

    public function ensureBudgetEngaged(Engagement $engagement): void
    {
        $ligne = $engagement->getLigneBudgetaire();
        if ($ligne === null || $engagement->isBudgetEngage()) {
            return;
        }

        $this->budgetLigneService->engager($ligne, (float) $engagement->getMontant());
        $engagement->setBudgetEngage(true);
    }

    public function changeStatut(
        Engagement $engagement,
        string $newStatut,
        User $actor,
        ?string $motifRejet = null,
    ): void {
        $current = $engagement->getStatut();
        if ($current === $newStatut) {
            return;
        }

        if ($newStatut === 'Réglé') {
            throw new \DomainException('Utilisez l\'endpoint /api/reglements pour enregistrer un règlement');
        }

        if ($newStatut === 'Visé') {
            $this->ensureBudgetEngaged($engagement);
            $engagement->setVisePar($actor);
            $engagement->setDateVisa(new \DateTimeImmutable());
            $engagement->setMotifRejet(null);
        }

        if ($newStatut === 'Rejeté') {
            $motif = trim((string) $motifRejet);
            if ($motif === '') {
                throw new \DomainException('Le motif de rejet est obligatoire');
            }

            $engagement->setMotifRejet($motif);
            $this->releaseBudget($engagement);
        }

        $engagement->setStatut($newStatut);
    }

    public function releaseBudget(Engagement $engagement): void
    {
        if (!$engagement->isBudgetEngage()) {
            return;
        }

        $ligne = $engagement->getLigneBudgetaire();
        if ($ligne !== null) {
            $this->budgetLigneService->liberer($ligne, (float) $engagement->getMontant());
        }

        $engagement->setBudgetEngage(false);
    }

    public function onReglement(Engagement $engagement): void
    {
        if ($engagement->isBudgetDecaisse()) {
            return;
        }

        $ligne = $engagement->getLigneBudgetaire();
        if ($ligne === null) {
            throw new \DomainException('Aucune ligne budgétaire associée à cet engagement');
        }

        $budget = $ligne->getBudget();
        if ($budget === null) {
            throw new \DomainException('Aucun budget associé à la ligne budgétaire');
        }

        $montant = (float) $engagement->getMontant();
        $this->budgetLigneService->decaisser($ligne, $budget, $montant);

        if ($engagement->isBudgetEngage()) {
            $this->budgetLigneService->liberer($ligne, $montant);
            $engagement->setBudgetEngage(false);
        }

        $engagement->setBudgetDecaisse(true);
    }
}
