<?php

namespace App\Command;

use App\Data\GabonPostesComptablesSeedData;
use App\Entity\Administration;
use App\Entity\Fournisseur;
use App\Entity\PosteComptable;
use App\Repository\ProvinceRepository;
use App\Service\AdministrationSetupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-gabon-referentiels',
    description: 'Vide et recharge fournisseurs, administrations, postes comptables, UO et lignes budgétaires',
)]
final class SeedGabonReferentielsCommand extends Command
{
    private const ADMIN_TEMPLATES = [
        ['suffix' => '01', 'label' => 'Direction Provinciale du Budget'],
        ['suffix' => '02', 'label' => 'Direction Provinciale des Finances'],
        ['suffix' => '03', 'label' => 'Trésor Public Provincial'],
        ['suffix' => '04', 'label' => 'Direction du Contrôle Budgétaire'],
    ];

    private const UO_NAMES = [
        'Service du Budget',
        'Service de la Dépense',
        'Service du Recouvrement',
        'Service de Contrôle de Gestion',
    ];

    private const LIGNE_TEMPLATES = [
        ['suffix' => '01', 'libelle' => 'Rémunération des agents publics', 'part' => 0.35],
        ['suffix' => '02', 'libelle' => 'Achats de biens et services', 'part' => 0.30],
        ['suffix' => '03', 'libelle' => 'Investissements et équipements', 'part' => 0.25],
        ['suffix' => '04', 'libelle' => 'Transferts et interventions', 'part' => 0.10],
    ];

    private const FOURNISSEURS = [
        ['nom' => 'SEEG', 'adresse' => 'Boulevard Triomphal, Libreville', 'telephone' => '011763000', 'nif' => 'GA-SEEG-001'],
        ['nom' => 'Gabon Télécom', 'adresse' => 'Rue Ange Mba, Libreville', 'telephone' => '011770000', 'nif' => 'GA-GT-002'],
        ['nom' => 'BGFIBank Gabon', 'adresse' => 'Avenue du Colonel Parant, Libreville', 'telephone' => '011442000', 'nif' => 'GA-BGFI-003'],
        ['nom' => 'SOGARA', 'adresse' => 'Zone Industrielle, Port-Gentil', 'telephone' => '011552000', 'nif' => 'GA-SOG-004'],
        ['nom' => 'SETRAGE', 'adresse' => 'Route de Franceville, Libreville', 'telephone' => '011701000', 'nif' => 'GA-SET-005'],
        ['nom' => 'EIFFAGE Gabon', 'adresse' => 'Quartier Glass, Libreville', 'telephone' => '011445000', 'nif' => 'GA-EIF-006'],
        ['nom' => 'SOCOPAO', 'adresse' => 'Zone Portuaire, Owendo', 'telephone' => '011708000', 'nif' => 'GA-SCP-007'],
        ['nom' => 'BGFI Leasing', 'adresse' => 'Boulevard de l\'Indépendance, Libreville', 'telephone' => '011442500', 'nif' => 'GA-BGL-008'],
        ['nom' => 'Airtel Gabon', 'adresse' => 'Carrefour Dialogue, Libreville', 'telephone' => '074000000', 'nif' => 'GA-AIR-009'],
        ['nom' => 'Orabank Gabon', 'adresse' => 'Avenue Bouët, Libreville', 'telephone' => '011761000', 'nif' => 'GA-ORA-010'],
    ];

    public function __construct(
        private readonly ProvinceRepository $provinceRepository,
        private readonly AdministrationSetupService $setupService,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Confirmer la suppression des données existantes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force')) {
            $io->warning(
                'Cette commande supprime engagements, règlements, lignes, budgets, UO, administrations, postes comptables et fournisseurs.'
            );
            $io->text('Relancez avec --force pour exécuter.');

            return Command::FAILURE;
        }

        $conn = $this->em->getConnection();
        $conn->executeStatement('DELETE FROM reglement');
        $conn->executeStatement('DELETE FROM engagement');
        $conn->executeStatement('DELETE FROM ligne_budgetaire');
        $conn->executeStatement('DELETE FROM budget');
        $conn->executeStatement('DELETE FROM unite_operationnelle');
        $conn->executeStatement('DELETE FROM administration');
        $conn->executeStatement('DELETE FROM poste_comptable');
        $conn->executeStatement('DELETE FROM fournisseur');

        foreach (['reglement', 'engagement', 'ligne_budgetaire', 'budget', 'unite_operationnelle', 'administration', 'poste_comptable', 'fournisseur'] as $table) {
            $conn->executeStatement(
                "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), 1, false)"
            );
        }

        foreach (self::FOURNISSEURS as $data) {
            $fournisseur = new Fournisseur();
            $fournisseur->setNom($data['nom']);
            $fournisseur->setAdresse($data['adresse']);
            $fournisseur->setTelephone($data['telephone']);
            $fournisseur->setNif($data['nif']);
            $this->em->persist($fournisseur);
        }

        $provinces = $this->provinceRepository->findBy([], ['id' => 'ASC']);
        $adminCount = 0;
        $uoCount = 0;
        $ligneCount = 0;
        $posteCount = 0;

        foreach ($provinces as $index => $province) {
            $provinceNom = $province->getNom() ?? 'Province';
            $provinceCode = $province->getCode() ?? ('P' . $province->getId());
            $enveloppeUo = $this->enveloppeParProvince($index);

            foreach (self::ADMIN_TEMPLATES as $adminIdx => $adminTpl) {
                $admin = new Administration();
                $admin->setNom(sprintf('%s - %s', $adminTpl['label'], $provinceNom));
                $admin->setCode(sprintf('ADM-%s-%s', $provinceCode, $adminTpl['suffix']));
                $admin->setProvince($province);
                $this->em->persist($admin);
                ++$adminCount;

                $uoData = [
                    [
                        'nom' => self::UO_NAMES[$adminIdx],
                        'code' => sprintf('UO-%s-%s', $provinceCode, $adminTpl['suffix']),
                        'budget_annee' => 2026,
                        'lignes_budgetaires' => $this->buildLignes($provinceCode, $adminTpl['suffix'], $enveloppeUo),
                    ],
                ];

                $this->setupService->attachUnitesAndLignes($admin, $uoData, $this->em);
                ++$uoCount;
                $ligneCount += count(self::LIGNE_TEMPLATES);
            }

            foreach ($this->resolvePostesForProvince($provinceNom) as $posteTpl) {
                $poste = new PosteComptable();
                $poste->setCode($posteTpl['code']);
                $poste->setLibelle($posteTpl['libelle']);
                $poste->setDescription($posteTpl['description']);
                $poste->setType($posteTpl['type']);
                $poste->setProvince($province);
                $this->em->persist($poste);
                ++$posteCount;
            }
        }

        $this->em->flush();

        $io->success('Référentiels Gabon rechargés.');
        $io->table(
            ['Élément', 'Quantité'],
            [
                ['Provinces', (string) count($provinces)],
                ['Administrations (4 / province)', (string) $adminCount],
                ['Unités opérationnelles (4 / province)', (string) $uoCount],
                ['Lignes budgétaires (4 / UO)', (string) $ligneCount],
                ['Postes comptables (réseau DGCPT)', (string) $posteCount],
                ['Fournisseurs', (string) count(self::FOURNISSEURS)],
            ]
        );

        return Command::SUCCESS;
    }

    private function enveloppeParProvince(int $index): float
    {
        $enveloppes = [100_000_000, 75_000_000, 70_000_000, 65_000_000, 60_000_000, 55_000_000, 55_000_000, 80_000_000, 60_000_000];

        return $enveloppes[$index] ?? 60_000_000;
    }

    /**
     * @return list<array{code: string, libelle: string, montant_alloue: float}>
     */
    private function buildLignes(string $provinceCode, string $adminSuffix, float $enveloppeUo): array
    {
        $lignes = [];
        $remaining = $enveloppeUo;

        foreach (self::LIGNE_TEMPLATES as $idx => $tpl) {
            $isLast = $idx === count(self::LIGNE_TEMPLATES) - 1;
            $montant = $isLast
                ? round($remaining, 2)
                : round($enveloppeUo * $tpl['part'], 2);
            $remaining -= $montant;

            $lignes[] = [
                'code' => sprintf('LB-%s-%s-%s', $provinceCode, $adminSuffix, $tpl['suffix']),
                'libelle' => $tpl['libelle'],
                'montant_alloue' => $montant,
            ];
        }

        return $lignes;
    }

    /**
     * @return list<array{code: string, libelle: string, description: string, type: string}>
     */
    private function resolvePostesForProvince(string $provinceNom): array
    {
        $byKey = GabonPostesComptablesSeedData::byProvinceKey();
        $key = GabonPostesComptablesSeedData::provinceKeyFromNom($provinceNom);

        if (isset($byKey[$key])) {
            return $byKey[$key];
        }

        $stripped = preg_replace('/^(la|le|l|du|de|des)-/', '', $key) ?? $key;
        if ($stripped !== $key && isset($byKey[$stripped])) {
            return $byKey[$stripped];
        }

        foreach ($byKey as $knownKey => $postes) {
            if (str_contains($key, $knownKey) || str_contains($knownKey, $stripped)) {
                return $postes;
            }
        }

        return [];
    }
}
