<?php

namespace App\Command;

use App\Repository\EngagementRepository;
use App\Service\EngagementWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-reglement-budget',
    description: 'Applique le décaissement budgétaire aux engagements déjà réglés sans budget_decaisse',
)]
class SyncReglementBudgetCommand extends Command
{
    public function __construct(
        private readonly EngagementRepository $engagementRepository,
        private readonly EngagementWorkflowService $workflowService,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $engagements = $this->engagementRepository->createQueryBuilder('e')
            ->andWhere('e.statut = :statut')
            ->andWhere('e.budgetDecaisse = false')
            ->setParameter('statut', 'Réglé')
            ->getQuery()
            ->getResult();

        if ($engagements === []) {
            $io->success('Aucun engagement à synchroniser.');

            return Command::SUCCESS;
        }

        $synced = 0;
        $errors = 0;

        foreach ($engagements as $engagement) {
            try {
                $this->workflowService->onReglement($engagement);
                ++$synced;
                $io->writeln(sprintf(
                    '  ✓ Engagement #%d — %.2f FCFA décaissé',
                    $engagement->getId(),
                    (float) $engagement->getMontant()
                ));
            } catch (\Throwable $e) {
                ++$errors;
                $io->error(sprintf(
                    'Engagement #%d : %s',
                    $engagement->getId(),
                    $e->getMessage()
                ));
            }
        }

        $this->em->flush();

        if ($errors > 0) {
            $io->warning(sprintf('%d synchronisé(s), %d erreur(s).', $synced, $errors));

            return Command::FAILURE;
        }

        $io->success(sprintf('%d engagement(s) synchronisé(s).', $synced));

        return Command::SUCCESS;
    }
}
