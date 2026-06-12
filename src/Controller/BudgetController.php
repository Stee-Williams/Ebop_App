<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\Budget;
use App\Entity\Province;
use App\Repository\BudgetRepository;
use App\Repository\LigneBudgetaireRepository;
use App\Repository\UniteOperationnelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Security\Voter\PermissionVoter;
use App\Util\BudgetMath;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budgets')]
final class BudgetController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_budgets_list', methods: ['GET'])]
    public function list(BudgetRepository $budgetRepository): JsonResponse
    {
        $budgets = $budgetRepository->findBy([], ['annee' => 'DESC', 'id' => 'ASC']);

        return $this->success(array_map(fn (Budget $b) => $this->serialize($b), $budgets));
    }

    #[Route('/consultation', name: 'api_budgets_consultation', methods: ['GET'])]
    #[IsGranted(PermissionVoter::READ_BUDGET)]
    public function consultation(
        BudgetRepository $budgetRepository,
        LigneBudgetaireRepository $ligneBudgetaireRepository,
    ): JsonResponse {
        $budgets = $budgetRepository->findBy([], ['annee' => 'DESC', 'id' => 'ASC']);
        $lignes = $ligneBudgetaireRepository->findBy([], ['code' => 'ASC', 'id' => 'ASC']);

        $budgetsData = array_map(fn (Budget $b) => $this->serialize($b), $budgets);

        $totalBudgetGlobal = 0.0;
        foreach ($budgets as $budget) {
            $totalBudgetGlobal += (float) $budget->getMontant();
        }

        $lignesData = [];
        $totalAlloue = 0.0;
        $totalConsomme = 0.0;

        foreach ($lignes as $ligne) {
            $alloue = (float) $ligne->getMontantAlloue();
            $consomme = (float) $ligne->getMontantUtilise() + (float) $ligne->getMontantDecaisse();
            $budget = $ligne->getBudget();

            $totalAlloue += $alloue;
            $totalConsomme += $consomme;

            $uo = $budget?->getUniteOperationnelle();
            $administration = $uo?->getAdministration();
            $province = $administration?->getProvince();

            $lignesData[] = [
                'id' => $ligne->getId(),
                'code' => $ligne->getCode(),
                'libelle' => $ligne->getLibelle(),
                'montant_alloue' => $alloue,
                'montant_utilise' => $consomme,
                'montant_decaisse' => $consomme,
                'montant_disponible' => max(0, $alloue - $consomme),
                'taux_utilisation' => BudgetMath::tauxUtilisation($consomme, $alloue),
                'budget_id' => $budget?->getId(),
                'budget_libelle' => $budget?->getLibelle(),
                'annee' => $budget?->getAnnee(),
                'province_id' => $province?->getId(),
                'province_nom' => $province?->getNom(),
                'administration_id' => $administration?->getId(),
                'administration_nom' => $administration?->getNom(),
                'unite_operationnelle_id' => $uo?->getId(),
                'unite_operationnelle_nom' => $uo?->getNom(),
                'budget_montant' => $budget !== null ? (float) $budget->getMontant() : null,
            ];
        }

        return $this->json([
            'success' => true,
            'budgets' => $budgetsData,
            'lignes' => $lignesData,
            'stats' => [
                'total_alloue' => $totalAlloue,
                'total_utilise' => $totalConsomme,
                'total_disponible' => max(0, $totalAlloue - $totalConsomme),
                'total_decaisse' => $totalConsomme,
                'total_budget_global' => $totalBudgetGlobal,
                'taux_global' => BudgetMath::tauxUtilisation($totalConsomme, $totalAlloue),
                'nombre_lignes' => count($lignesData),
                'nombre_budgets' => count($budgetsData),
            ],
        ]);
    }

    #[Route('/{id}', name: 'api_budgets_show', methods: ['GET'])]
    public function show(int $id, BudgetRepository $budgetRepository): JsonResponse
    {
        $budget = $budgetRepository->find($id);
        if (!$budget) {
            return $this->error('Budget introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->success($this->serialize($budget, true));
    }

    #[Route('', name: 'api_budgets_create', methods: ['POST'])]
    public function create(
        Request $request,
        UniteOperationnelleRepository $uoRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['annee']) || empty($data['libelle']) || !isset($data['montant'])) {
            return $this->error('Année, libellé et montant sont obligatoires');
        }

        $budget = new Budget();
        $budget->setAnnee((int) $data['annee']);
        $budget->setLibelle($data['libelle']);
        $budget->setMontant((string) $data['montant']);

        if (!empty($data['unite_operationnelle_id'])) {
            $uo = $uoRepository->find($data['unite_operationnelle_id']);
            if (!$uo) {
                return $this->error('Unité opérationnelle introuvable', Response::HTTP_NOT_FOUND);
            }
            $budget->setUniteOperationnelle($uo);
        }

        $em->persist($budget);
        $em->flush();

        return $this->success(['message' => 'Budget créé', 'data' => $this->serialize($budget)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_budgets_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        BudgetRepository $budgetRepository,
        UniteOperationnelleRepository $uoRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $budget = $budgetRepository->find($id);
        if (!$budget) {
            return $this->error('Budget introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if (isset($data['annee'])) {
            $budget->setAnnee((int) $data['annee']);
        }
        if (isset($data['libelle'])) {
            $budget->setLibelle($data['libelle']);
        }
        if (isset($data['montant'])) {
            $budget->setMontant((string) $data['montant']);
        }
        if (array_key_exists('unite_operationnelle_id', $data)) {
            if ($data['unite_operationnelle_id'] === null) {
                $budget->setUniteOperationnelle(null);
            } else {
                $uo = $uoRepository->find($data['unite_operationnelle_id']);
                if (!$uo) {
                    return $this->error('Unité opérationnelle introuvable', Response::HTTP_NOT_FOUND);
                }
                $budget->setUniteOperationnelle($uo);
            }
        }

        $em->flush();

        return $this->success(['message' => 'Budget mis à jour', 'data' => $this->serialize($budget)]);
    }

    #[Route('/{id}', name: 'api_budgets_delete', methods: ['DELETE'])]
    public function delete(int $id, BudgetRepository $budgetRepository, EntityManagerInterface $em): JsonResponse
    {
        $budget = $budgetRepository->find($id);
        if (!$budget) {
            return $this->error('Budget introuvable', Response::HTTP_NOT_FOUND);
        }

        $em->remove($budget);
        $em->flush();

        return $this->success(['message' => 'Budget supprimé']);
    }

    private function getProvinceFromBudget(Budget $budget): ?Province
    {
        return $budget
            ->getUniteOperationnelle()
            ?->getAdministration()
            ?->getProvince();
    }

    private function serialize(Budget $budget, bool $detailed = false): array
    {
        $uo = $budget->getUniteOperationnelle();
        $administration = $uo?->getAdministration();
        $province = $administration?->getProvince();
        $data = [
            'id' => $budget->getId(),
            'annee' => $budget->getAnnee(),
            'libelle' => $budget->getLibelle(),
            'montant' => (float) $budget->getMontant(),
            'unite_operationnelle_id' => $uo?->getId(),
            'unite_operationnelle' => $uo?->getNom(),
            'administration_id' => $administration?->getId(),
            'administration_nom' => $administration?->getNom(),
            'province_id' => $province?->getId(),
            'province_nom' => $province?->getNom(),
            'lignes_count' => $budget->getLigneBudgetaires()->count(),
        ];

        if ($detailed) {
            $data['lignes'] = array_map(static fn ($l) => [
                'id' => $l->getId(),
                'code' => $l->getCode(),
                'libelle' => $l->getLibelle(),
                'montant_alloue' => (float) $l->getMontantAlloue(),
                'montant_utilise' => (float) $l->getMontantUtilise(),
            ], $budget->getLigneBudgetaires()->toArray());
        }

        return $data;
    }
}
