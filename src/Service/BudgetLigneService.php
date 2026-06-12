<?php

namespace App\Service;

use App\Entity\Budget;
use App\Entity\LigneBudgetaire;

final class BudgetLigneService
{
    public function getMontantConsomme(LigneBudgetaire $ligne): float
    {
        return (float) $ligne->getMontantUtilise() + (float) $ligne->getMontantDecaisse();
    }

    public function getDisponible(LigneBudgetaire $ligne): float
    {
        $alloue = (float) $ligne->getMontantAlloue();

        return max(0, $alloue - $this->getMontantConsomme($ligne));
    }

    public function engager(LigneBudgetaire $ligne, float $montant): void
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }

        $disponible = $this->getDisponible($ligne);
        if ($montant > $disponible + 0.001) {
            throw new \DomainException(
                sprintf(
                    'Budget insuffisant sur la ligne « %s » (disponible : %.2f, demandé : %.2f)',
                    $ligne->getLibelle(),
                    $disponible,
                    $montant
                )
            );
        }

        $nouveau = bcadd((string) $ligne->getMontantUtilise(), (string) $montant, 2);
        $ligne->setMontantUtilise($nouveau);
    }

    public function liberer(LigneBudgetaire $ligne, float $montant): void
    {
        if ($montant <= 0) {
            return;
        }

        $utilise = (float) $ligne->getMontantUtilise();
        $nouveau = max(0, $utilise - $montant);
        $ligne->setMontantUtilise(number_format($nouveau, 2, '.', ''));
    }

    /**
     * Décaissement : enregistre le paiement sur la ligne (montant_decaisse) et retranche
     * l'enveloppe du budget UO. L'enveloppe allouée de la ligne reste inchangée.
     */
    public function decaisser(LigneBudgetaire $ligne, Budget $budget, float $montant): void
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }

        $disponible = $this->getDisponible($ligne);
        if ($montant > $disponible + 0.001) {
            throw new \DomainException(
                sprintf(
                    'Budget insuffisant sur la ligne « %s » (disponible : %.2f, règlement : %.2f)',
                    $ligne->getLibelle(),
                    $disponible,
                    $montant
                )
            );
        }

        $decaisse = (float) $ligne->getMontantDecaisse();
        $ligne->setMontantDecaisse(number_format($decaisse + $montant, 2, '.', ''));

        $budgetMontant = (float) $budget->getMontant();
        if ($montant > $budgetMontant + 0.001) {
            throw new \DomainException(
                sprintf(
                    'Montant insuffisant sur le budget « %s » (disponible : %.2f, règlement : %.2f)',
                    $budget->getLibelle(),
                    $budgetMontant,
                    $montant
                )
            );
        }

        $budget->setMontant(number_format($budgetMontant - $montant, 2, '.', ''));
    }
}
