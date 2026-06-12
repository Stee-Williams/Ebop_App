<?php

namespace App\Command;

use App\Repository\BudgetRepository;
use App\Repository\LigneBudgetaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reset-budget',
    description: 'Réinitialise les montants utilisés et réaligne les lignes budgétaires sur les enveloppes',
)]
class ResetBudgetCommand extends Command
{
    public function __construct(
        private readonly BudgetRepository $budgetRepository,
        private readonly LigneBudgetaireRepository $ligneBudgetaireRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $lignes = $this->ligneBudgetaireRepository->findAll();
        $released = 0.0;

        foreach ($lignes as $ligne) {
            $utilise = (float) $ligne->getMontantUtilise();
            if ($utilise > 0) {
                $alloue = (float) $ligne->getMontantAlloue();
                $ligne->setMontantAlloue(number_format($alloue + $utilise, 2, '.', ''));
                $ligne->setMontantUtilise('0');
                $released += $utilise;
            }
            $ligne->setMontantDecaisse('0');
        }

        $budgets = $this->budgetRepository->findAll();
        $realigned = 0;

        foreach ($budgets as $budget) {
            $budgetLignes = array_values(
                array_filter($lignes, fn ($l) => $l->getBudget()?->getId() === $budget->getId())
            );

            if ($budgetLignes === []) {
                continue;
            }

            $envelope = (float) $budget->getMontant();
            $sumAlloue = 0.0;
            foreach ($budgetLignes as $ligne) {
                $sumAlloue += (float) $ligne->getMontantAlloue();
            }

            if ($sumAlloue <= 0) {
                $part = $envelope / count($budgetLignes);
                foreach ($budgetLignes as $ligne) {
                    $ligne->setMontantAlloue(number_format($part, 2, '.', ''));
                }
                ++$realigned;
                continue;
            }

            if (abs($sumAlloue - $envelope) < 0.01) {
                continue;
            }

            $factor = $envelope / $sumAlloue;
            $remaining = $envelope;

            foreach ($budgetLignes as $index => $ligne) {
                if ($index === count($budgetLignes) - 1) {
                    $nouveau = $remaining;
                } else {
                    $nouveau = round((float) $ligne->getMontantAlloue() * $factor, 2);
                    $remaining -= $nouveau;
                }

                $ligne->setMontantAlloue(number_format(max(0, $nouveau), 2, '.', ''));
            }

            ++$realigned;
        }

        $this->em->flush();

        $totals = $this->em->getConnection()->fetchAssociative(
            'SELECT
                (SELECT COALESCE(SUM(montant::numeric), 0) FROM budget) AS budget,
                (SELECT COALESCE(SUM(montant_alloue::numeric), 0) FROM ligne_budgetaire) AS alloue,
                (SELECT COALESCE(SUM(montant_utilise::numeric), 0) FROM ligne_budgetaire) AS utilise'
        );

        $io->success('Budget réinitialisé.');
        $io->table(
            ['Indicateur', 'Valeur'],
            [
                ['Réservations libérées', number_format($released, 2, '.', ' ') . ' FCFA'],
                ['Budgets réalignés', (string) $realigned],
                ['Total enveloppes (budget)', number_format((float) $totals['budget'], 2, '.', ' ') . ' FCFA'],
                ['Total alloué (lignes)', number_format((float) $totals['alloue'], 2, '.', ' ') . ' FCFA'],
                ['Total utilisé (lignes)', number_format((float) $totals['utilise'], 2, '.', ' ') . ' FCFA'],
            ]
        );

        return Command::SUCCESS;
    }
}
