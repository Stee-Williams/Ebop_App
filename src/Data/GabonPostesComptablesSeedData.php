<?php

namespace App\Data;

/**
 * Référentiel des 48 postes comptables du réseau DGCPT au Gabon :
 * 9 trésoreries provinciales, 7 recettes-perceptions, 32 perceptions.
 *
 * @return array<string, list<array{code: string, libelle: string, description: string, type: string}>>
 */
final class GabonPostesComptablesSeedData
{
    public static function byProvinceKey(): array
    {
        return [
            'estuaire' => [
                ['code' => 'TP-G1', 'libelle' => 'Trésorerie provinciale de l\'Estuaire', 'description' => 'Trésorerie provinciale — Libreville', 'type' => 'tresorerie_provinciale'],
                ['code' => 'RP-G1-01', 'libelle' => 'Recette-perception de Libreville', 'description' => 'Recette-perception — Libreville', 'type' => 'recette_perception'],
                ['code' => 'PC-G1-01', 'libelle' => 'Perception de Libreville-Centre', 'description' => 'Perception — Libreville', 'type' => 'perception'],
                ['code' => 'PC-G1-02', 'libelle' => 'Perception d\'Akanda', 'description' => 'Perception — Akanda', 'type' => 'perception'],
                ['code' => 'PC-G1-03', 'libelle' => 'Perception de Ntoum', 'description' => 'Perception — Ntoum', 'type' => 'perception'],
                ['code' => 'PC-G1-04', 'libelle' => 'Perception de Kango', 'description' => 'Perception — Kango', 'type' => 'perception'],
                ['code' => 'PC-G1-05', 'libelle' => 'Perception de Cocobeach', 'description' => 'Perception — Cocobeach', 'type' => 'perception'],
                ['code' => 'PC-G1-06', 'libelle' => 'Perception de Ndzomoe', 'description' => 'Perception — Ndzomoe', 'type' => 'perception'],
            ],
            'haut-ogooue' => [
                ['code' => 'TP-G2', 'libelle' => 'Trésorerie provinciale du Haut-Ogooué', 'description' => 'Trésorerie provinciale — Franceville', 'type' => 'tresorerie_provinciale'],
                ['code' => 'RP-G2-01', 'libelle' => 'Recette-perception de Franceville', 'description' => 'Recette-perception — Franceville', 'type' => 'recette_perception'],
                ['code' => 'PC-G2-01', 'libelle' => 'Perception de Franceville', 'description' => 'Perception — Franceville', 'type' => 'perception'],
                ['code' => 'PC-G2-02', 'libelle' => 'Perception de Moanda', 'description' => 'Perception — Moanda', 'type' => 'perception'],
                ['code' => 'PC-G2-03', 'libelle' => 'Perception de Mounana', 'description' => 'Perception — Mounana', 'type' => 'perception'],
                ['code' => 'PC-G2-04', 'libelle' => 'Perception de Lékoni', 'description' => 'Perception — Lékoni', 'type' => 'perception'],
            ],
            'moyen-ogooue' => [
                ['code' => 'TP-G3', 'libelle' => 'Trésorerie provinciale du Moyen-Ogooué', 'description' => 'Trésorerie provinciale — Lambaréné', 'type' => 'tresorerie_provinciale'],
                ['code' => 'RP-G3-01', 'libelle' => 'Recette-perception de Lambaréné', 'description' => 'Recette-perception — Lambaréné', 'type' => 'recette_perception'],
                ['code' => 'PC-G3-01', 'libelle' => 'Perception de Lambaréné', 'description' => 'Perception — Lambaréné', 'type' => 'perception'],
                ['code' => 'PC-G3-02', 'libelle' => 'Perception de Ndjolé', 'description' => 'Perception — Ndjolé', 'type' => 'perception'],
                ['code' => 'PC-G3-03', 'libelle' => 'Perception de Lastoursville', 'description' => 'Perception — Lastoursville', 'type' => 'perception'],
            ],
            'ngounie' => [
                ['code' => 'TP-G4', 'libelle' => 'Trésorerie provinciale de la Ngounié', 'description' => 'Trésorerie provinciale — Mouila', 'type' => 'tresorerie_provinciale'],
                ['code' => 'RP-G4-01', 'libelle' => 'Recette-perception de Mouila', 'description' => 'Recette-perception — Mouila', 'type' => 'recette_perception'],
                ['code' => 'PC-G4-01', 'libelle' => 'Perception de Mouila', 'description' => 'Perception — Mouila', 'type' => 'perception'],
                ['code' => 'PC-G4-02', 'libelle' => 'Perception de Ndendé', 'description' => 'Perception — Ndendé', 'type' => 'perception'],
                ['code' => 'PC-G4-03', 'libelle' => 'Perception de Fougamou', 'description' => 'Perception — Fougamou', 'type' => 'perception'],
            ],
            'nyanga' => [
                ['code' => 'TP-G5', 'libelle' => 'Trésorerie provinciale de la Nyanga', 'description' => 'Trésorerie provinciale — Tchibanga', 'type' => 'tresorerie_provinciale'],
                ['code' => 'PC-G5-01', 'libelle' => 'Perception de Tchibanga', 'description' => 'Perception — Tchibanga', 'type' => 'perception'],
                ['code' => 'PC-G5-02', 'libelle' => 'Perception de Moabi', 'description' => 'Perception — Moabi', 'type' => 'perception'],
            ],
            'ogooue-ivindo' => [
                ['code' => 'TP-G6', 'libelle' => 'Trésorerie provinciale de l\'Ogooué-Ivindo', 'description' => 'Trésorerie provinciale — Makokou', 'type' => 'tresorerie_provinciale'],
                ['code' => 'RP-G6-01', 'libelle' => 'Recette-perception de Makokou', 'description' => 'Recette-perception — Makokou', 'type' => 'recette_perception'],
                ['code' => 'PC-G6-01', 'libelle' => 'Perception de Makokou', 'description' => 'Perception — Makokou', 'type' => 'perception'],
                ['code' => 'PC-G6-02', 'libelle' => 'Perception de Mékambo', 'description' => 'Perception — Mékambo', 'type' => 'perception'],
                ['code' => 'PC-G6-03', 'libelle' => 'Perception de Booué', 'description' => 'Perception — Booué', 'type' => 'perception'],
                ['code' => 'PC-G6-04', 'libelle' => 'Perception d\'Ovan', 'description' => 'Perception — Ovan', 'type' => 'perception'],
            ],
            'ogooue-lolo' => [
                ['code' => 'TP-G7', 'libelle' => 'Trésorerie provinciale de l\'Ogooué-Lolo', 'description' => 'Trésorerie provinciale — Koulamoutou', 'type' => 'tresorerie_provinciale'],
                ['code' => 'PC-G7-01', 'libelle' => 'Perception de Koulamoutou', 'description' => 'Perception — Koulamoutou', 'type' => 'perception'],
                ['code' => 'PC-G7-02', 'libelle' => 'Perception de Pala', 'description' => 'Perception — Pala', 'type' => 'perception'],
            ],
            'ogooue-maritime' => [
                ['code' => 'TP-G8', 'libelle' => 'Trésorerie provinciale de l\'Ogooué-Maritime', 'description' => 'Trésorerie provinciale — Port-Gentil', 'type' => 'tresorerie_provinciale'],
                ['code' => 'RP-G8-01', 'libelle' => 'Recette-perception de Port-Gentil', 'description' => 'Recette-perception — Port-Gentil', 'type' => 'recette_perception'],
                ['code' => 'PC-G8-01', 'libelle' => 'Perception de Port-Gentil', 'description' => 'Perception — Port-Gentil', 'type' => 'perception'],
                ['code' => 'PC-G8-02', 'libelle' => 'Perception de Gamba', 'description' => 'Perception — Gamba', 'type' => 'perception'],
                ['code' => 'PC-G8-03', 'libelle' => 'Perception de Mayumba', 'description' => 'Perception — Mayumba', 'type' => 'perception'],
                ['code' => 'PC-G8-04', 'libelle' => 'Perception d\'Omboué', 'description' => 'Perception — Omboué', 'type' => 'perception'],
                ['code' => 'PC-G8-05', 'libelle' => 'Perception de Ndendé-Océan', 'description' => 'Perception — Ndendé', 'type' => 'perception'],
            ],
            'woleu-ntem' => [
                ['code' => 'TP-G9', 'libelle' => 'Trésorerie provinciale du Woleu-Ntem', 'description' => 'Trésorerie provinciale — Oyem', 'type' => 'tresorerie_provinciale'],
                ['code' => 'RP-G9-01', 'libelle' => 'Recette-perception d\'Oyem', 'description' => 'Recette-perception — Oyem', 'type' => 'recette_perception'],
                ['code' => 'PC-G9-01', 'libelle' => 'Perception d\'Oyem', 'description' => 'Perception — Oyem', 'type' => 'perception'],
                ['code' => 'PC-G9-02', 'libelle' => 'Perception de Bitam', 'description' => 'Perception — Bitam', 'type' => 'perception'],
                ['code' => 'PC-G9-03', 'libelle' => 'Perception de Minvoul', 'description' => 'Perception — Minvoul', 'type' => 'perception'],
            ],
        ];
    }

    public static function provinceKeyFromNom(string $nom): string
    {
        $normalized = mb_strtolower($nom);
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? $normalized;

        return trim($normalized, '-');
    }
}
