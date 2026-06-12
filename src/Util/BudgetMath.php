<?php

namespace App\Util;

final class BudgetMath
{
    /**
     * Taux d'utilisation en %, avec précision adaptée aux petits montants.
     */
    public static function tauxUtilisation(float $consomme, float $alloue): float
    {
        if ($alloue <= 0) {
            return 0.0;
        }

        $taux = ($consomme / $alloue) * 100;

        if ($taux > 0 && $taux < 0.1) {
            return round($taux, 3);
        }

        if ($taux < 10) {
            return round($taux, 2);
        }

        return round($taux, 1);
    }
}
