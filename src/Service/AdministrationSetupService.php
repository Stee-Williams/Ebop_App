<?php

namespace App\Service;

use App\Entity\Administration;
use App\Entity\Budget;
use App\Entity\LigneBudgetaire;
use App\Entity\UniteOperationnelle;
use Doctrine\ORM\EntityManagerInterface;

final class AdministrationSetupService
{
    /**
     * @param list<array<string, mixed>> $unitesData
     */
    public function attachUnitesAndLignes(
        Administration $administration,
        array $unitesData,
        EntityManagerInterface $em,
    ): void {
        foreach ($unitesData as $uoData) {
            if (!is_array($uoData) || empty($uoData['nom'])) {
                continue;
            }

            $uo = new UniteOperationnelle();
            $uo->setNom(trim((string) $uoData['nom']));
            $uo->setCode(!empty($uoData['code']) ? trim((string) $uoData['code']) : null);
            $uo->setAdministration($administration);
            $em->persist($uo);

            $lignesData = $uoData['lignes_budgetaires'] ?? [];
            if (!is_array($lignesData) || $lignesData === []) {
                continue;
            }

            $annee = (int) ($uoData['budget_annee'] ?? (int) date('Y'));
            $totalMontant = 0.0;

            foreach ($lignesData as $ligneData) {
                if (!is_array($ligneData) || empty($ligneData['libelle']) || !isset($ligneData['montant_alloue'])) {
                    continue;
                }
                $totalMontant += (float) $ligneData['montant_alloue'];
            }

            if ($totalMontant <= 0) {
                continue;
            }

            $budget = new Budget();
            $budget->setAnnee($annee);
            $budget->setLibelle(sprintf(
                'Budget %s - %s',
                $administration->getNom(),
                $uo->getNom()
            ));
            $budget->setMontant((string) $totalMontant);
            $budget->setUniteOperationnelle($uo);
            $em->persist($budget);

            foreach ($lignesData as $ligneData) {
                if (!is_array($ligneData) || empty($ligneData['libelle']) || !isset($ligneData['montant_alloue'])) {
                    continue;
                }

                $montant = (float) $ligneData['montant_alloue'];
                if ($montant <= 0) {
                    continue;
                }

                $ligne = new LigneBudgetaire();
                $ligne->setLibelle(trim((string) $ligneData['libelle']));
                $ligne->setCode(!empty($ligneData['code']) ? trim((string) $ligneData['code']) : null);
                $ligne->setMontantAlloue((string) $montant);
                $ligne->setMontantUtilise('0');
                $ligne->setBudget($budget);
                $em->persist($ligne);
            }
        }
    }
}
