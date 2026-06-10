<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\LigneBudgetaire;
use App\Repository\BudgetRepository;
use App\Repository\LigneBudgetaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/lignes-budgetaires')]
final class LigneBudgetaireController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_lignes_budgetaires_list', methods: ['GET'])]
    public function list(LigneBudgetaireRepository $repository): JsonResponse
    {
        $items = $repository->findBy([], ['code' => 'ASC']);

        return $this->success(array_map(fn (LigneBudgetaire $l) => $this->serialize($l), $items));
    }

    #[Route('/{id}', name: 'api_lignes_budgetaires_show', methods: ['GET'])]
    public function show(int $id, LigneBudgetaireRepository $repository): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Ligne budgétaire introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->success($this->serialize($item));
    }

    #[Route('', name: 'api_lignes_budgetaires_create', methods: ['POST'])]
    public function create(
        Request $request,
        BudgetRepository $budgetRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['libelle']) || !isset($data['montant_alloue'])) {
            return $this->error('Libellé et montant alloué sont obligatoires');
        }

        $item = new LigneBudgetaire();
        $item->setLibelle($data['libelle']);
        $item->setCode($data['code'] ?? null);
        $item->setMontantAlloue((string) $data['montant_alloue']);
        $item->setMontantUtilise((string) ($data['montant_utilise'] ?? 0));

        if (!empty($data['budget_id'])) {
            $budget = $budgetRepository->find($data['budget_id']);
            if (!$budget) {
                return $this->error('Budget introuvable', Response::HTTP_NOT_FOUND);
            }
            $item->setBudget($budget);
        }

        $em->persist($item);
        $em->flush();

        return $this->success(['message' => 'Ligne budgétaire créée', 'data' => $this->serialize($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_lignes_budgetaires_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        LigneBudgetaireRepository $repository,
        BudgetRepository $budgetRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Ligne budgétaire introuvable', Response::HTTP_NOT_FOUND);
        }

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
                $item->setBudget($budget);
            }
        }

        $em->flush();

        return $this->success(['message' => 'Ligne budgétaire mise à jour', 'data' => $this->serialize($item)]);
    }

    #[Route('/{id}', name: 'api_lignes_budgetaires_delete', methods: ['DELETE'])]
    public function delete(int $id, LigneBudgetaireRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Ligne budgétaire introuvable', Response::HTTP_NOT_FOUND);
        }

        $em->remove($item);
        $em->flush();

        return $this->success(['message' => 'Ligne budgétaire supprimée']);
    }

    private function serialize(LigneBudgetaire $item): array
    {
        $alloue = (float) $item->getMontantAlloue();
        $utilise = (float) $item->getMontantUtilise();
        $budget = $item->getBudget();

        return [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'libelle' => $item->getLibelle(),
            'montant_alloue' => $alloue,
            'montant_utilise' => $utilise,
            'montant_disponible' => $alloue - $utilise,
            'taux_utilisation' => $alloue > 0 ? round(($utilise / $alloue) * 100, 1) : 0,
            'budget_id' => $budget?->getId(),
            'budget_libelle' => $budget?->getLibelle(),
            'annee' => $budget?->getAnnee(),
        ];
    }
}
