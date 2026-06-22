<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\LigneBudgetaire;
use App\Repository\BudgetRepository;
use App\Repository\LigneBudgetaireRepository;
use App\Security\ProvinceScopeService;
use App\Security\Voter\PermissionVoter;
use App\Service\EngagementWorkflowService;
use App\Util\BudgetMath;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/lignes-budgetaires')]
final class LigneBudgetaireController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_lignes_budgetaires_list', methods: ['GET'])]
    #[IsGranted(PermissionVoter::READ_LIGNES_BUDGETAIRES)]
    public function list(LigneBudgetaireRepository $repository, ProvinceScopeService $provinceScope): JsonResponse
    {
        $items = $repository->findBy([], ['code' => 'ASC', 'id' => 'ASC']);
        $items = $provinceScope->filterByProvince(
            $items,
            fn (LigneBudgetaire $l) => $provinceScope->getProvinceIdFromLigne($l)
        );

        return $this->success(array_map(fn (LigneBudgetaire $l) => $this->serialize($l), $items));
    }

    #[Route('/{id}', name: 'api_lignes_budgetaires_show', methods: ['GET'])]
    #[IsGranted(PermissionVoter::READ_LIGNES_BUDGETAIRES)]
    public function show(int $id, LigneBudgetaireRepository $repository, ProvinceScopeService $provinceScope): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Ligne budgétaire introuvable', Response::HTTP_NOT_FOUND);
        }

        $provinceScope->assertCanAccessLigne($item);

        return $this->success($this->serialize($item));
    }

    #[Route('', name: 'api_lignes_budgetaires_create', methods: ['POST'])]
    #[IsGranted(PermissionVoter::MANAGE_LIGNES_BUDGETAIRES)]
    public function create(
        Request $request,
        BudgetRepository $budgetRepository,
        EntityManagerInterface $em,
        ProvinceScopeService $provinceScope,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['libelle']) || !isset($data['montant_alloue']) || empty($data['budget_id'])) {
            return $this->error('Libellé, montant alloué et budget sont obligatoires');
        }

        $budget = $budgetRepository->find($data['budget_id']);
        if (!$budget) {
            return $this->error('Budget introuvable', Response::HTTP_NOT_FOUND);
        }

        $provinceScope->assertCanAccessBudget($budget);

        $item = new LigneBudgetaire();
        $item->setLibelle(trim((string) $data['libelle']));
        $item->setCode(!empty($data['code']) ? trim((string) $data['code']) : null);
        $item->setMontantAlloue((string) $data['montant_alloue']);
        $item->setMontantUtilise((string) ($data['montant_utilise'] ?? 0));
        $item->setBudget($budget);

        $em->persist($item);
        $em->flush();

        return $this->success(['message' => 'Ligne budgétaire créée', 'data' => $this->serialize($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_lignes_budgetaires_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(PermissionVoter::MANAGE_LIGNES_BUDGETAIRES)]
    public function update(
        int $id,
        Request $request,
        LigneBudgetaireRepository $repository,
        BudgetRepository $budgetRepository,
        EntityManagerInterface $em,
        ProvinceScopeService $provinceScope,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Ligne budgétaire introuvable', Response::HTTP_NOT_FOUND);
        }

        $provinceScope->assertCanAccessLigne($item);

        $data = $this->decodeJson($request);
        if (isset($data['libelle'])) {
            $item->setLibelle($data['libelle']);
        }
        if (array_key_exists('code', $data)) {
            $item->setCode($data['code']);
        }
        if (isset($data['montant_alloue'])) {
            $item->setMontantAlloue((string) $data['montant_alloue']);
        }
        if (isset($data['montant_utilise'])) {
            $item->setMontantUtilise((string) $data['montant_utilise']);
        }
        if (array_key_exists('budget_id', $data)) {
            if ($data['budget_id'] === null) {
                $item->setBudget(null);
            } else {
                $budget = $budgetRepository->find($data['budget_id']);
                if (!$budget) {
                    return $this->error('Budget introuvable', Response::HTTP_NOT_FOUND);
                }
                $provinceScope->assertCanAccessBudget($budget);
                $item->setBudget($budget);
            }
        }

        $em->flush();

        return $this->success(['message' => 'Ligne budgétaire mise à jour', 'data' => $this->serialize($item)]);
    }

    #[Route('/{id}', name: 'api_lignes_budgetaires_delete', methods: ['DELETE'])]
    #[IsGranted(PermissionVoter::MANAGE_LIGNES_BUDGETAIRES)]
    public function delete(
        int $id,
        LigneBudgetaireRepository $repository,
        EngagementWorkflowService $workflow,
        EntityManagerInterface $em,
        ProvinceScopeService $provinceScope,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Ligne budgétaire introuvable', Response::HTTP_NOT_FOUND);
        }

        $provinceScope->assertCanAccessLigne($item);

        foreach ($item->getEngagements()->toArray() as $engagement) {
            $workflow->releaseBudget($engagement);
            $engagement->setLigneBudgetaire(null);
        }

        $em->remove($item);
        $em->flush();

        return $this->success(['message' => 'Ligne budgétaire supprimée']);
    }

    private function serialize(LigneBudgetaire $item): array
    {
        $alloue = (float) $item->getMontantAlloue();
        $consomme = (float) $item->getMontantUtilise() + (float) $item->getMontantDecaisse();
        $budget = $item->getBudget();
        $uo = $budget?->getUniteOperationnelle();
        $administration = $uo?->getAdministration();
        $province = $administration?->getProvince();

        return [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'libelle' => $item->getLibelle(),
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
        ];
    }
}
