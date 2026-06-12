<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\Administration;
use App\Entity\Budget;
use App\Entity\LigneBudgetaire;
use App\Entity\UniteOperationnelle;
use App\Repository\AdministrationRepository;
use App\Repository\ProvinceRepository;
use App\Security\Voter\PermissionVoter;
use App\Service\AdministrationSetupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/administrations')]
final class AdministrationController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_administrations_list', methods: ['GET'])]
    public function list(AdministrationRepository $repository): JsonResponse
    {
        $items = $repository->findBy([], ['nom' => 'ASC']);

        return $this->success(array_map(fn (Administration $a) => $this->serialize($a), $items));
    }

    #[Route('/{id}', name: 'api_administrations_show', methods: ['GET'])]
    public function show(int $id, AdministrationRepository $repository): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Administration introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->success($this->serialize($item, true));
    }

    #[Route('', name: 'api_administrations_create', methods: ['POST'])]
    #[IsGranted(PermissionVoter::MANAGE_ADMINISTRATIONS)]
    public function create(
        Request $request,
        ProvinceRepository $provinceRepository,
        AdministrationSetupService $setupService,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['nom']) || empty($data['code'])) {
            return $this->error('Le nom et le code sont obligatoires');
        }

        $item = new Administration();
        $item->setNom($data['nom']);
        $item->setCode($data['code']);

        if (!empty($data['province_id'])) {
            $province = $provinceRepository->find($data['province_id']);
            if (!$province) {
                return $this->error('Province introuvable', Response::HTTP_NOT_FOUND);
            }
            $item->setProvince($province);
        }

        $em->persist($item);

        $unitesData = $data['unites_operationnelles'] ?? [];
        if (is_array($unitesData) && $unitesData !== []) {
            $setupService->attachUnitesAndLignes($item, $unitesData, $em);
        }

        $em->flush();

        return $this->success(
            ['message' => 'Administration créée', 'data' => $this->serialize($item, true)],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_administrations_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(PermissionVoter::MANAGE_ADMINISTRATIONS)]
    public function update(
        int $id,
        Request $request,
        AdministrationRepository $repository,
        ProvinceRepository $provinceRepository,
        AdministrationSetupService $setupService,
        EntityManagerInterface $em,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Administration introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if (isset($data['nom'])) {
            $item->setNom($data['nom']);
        }
        if (isset($data['code'])) {
            $item->setCode($data['code']);
        }
        if (array_key_exists('province_id', $data)) {
            if ($data['province_id'] === null) {
                $item->setProvince(null);
            } else {
                $province = $provinceRepository->find($data['province_id']);
                if (!$province) {
                    return $this->error('Province introuvable', Response::HTTP_NOT_FOUND);
                }
                $item->setProvince($province);
            }
        }

        $unitesData = $data['unites_operationnelles'] ?? null;
        if (is_array($unitesData) && $unitesData !== []) {
            $setupService->attachUnitesAndLignes($item, $unitesData, $em);
        }

        $em->flush();

        return $this->success([
            'message' => 'Administration mise à jour',
            'data' => $this->serialize($item, true),
        ]);
    }

    #[Route('/{id}', name: 'api_administrations_delete', methods: ['DELETE'])]
    #[IsGranted(PermissionVoter::MANAGE_ADMINISTRATIONS)]
    public function delete(int $id, AdministrationRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Administration introuvable', Response::HTTP_NOT_FOUND);
        }

        $em->remove($item);
        $em->flush();

        return $this->success(['message' => 'Administration supprimée']);
    }

    private function serialize(Administration $item, bool $detailed = false): array
    {
        $data = [
            'id' => $item->getId(),
            'nom' => $item->getNom(),
            'code' => $item->getCode(),
            'province_id' => $item->getProvince()?->getId(),
            'province_nom' => $item->getProvince()?->getNom(),
            'unites_count' => $item->getUniteOperationnelles()->count(),
        ];

        if (!$detailed) {
            return $data;
        }

        $data['unites_operationnelles'] = array_map(
            fn (UniteOperationnelle $uo) => $this->serializeUnite($uo),
            $item->getUniteOperationnelles()->toArray()
        );

        return $data;
    }

    private function serializeUnite(UniteOperationnelle $uo): array
    {
        $lignes = [];
        $budgetAnnee = null;

        foreach ($uo->getBudgets() as $budget) {
            $budgetAnnee ??= $budget->getAnnee();
            foreach ($budget->getLigneBudgetaires() as $ligne) {
                $lignes[] = $this->serializeLigne($ligne, $budget);
            }
        }

        return [
            'id' => $uo->getId(),
            'nom' => $uo->getNom(),
            'code' => $uo->getCode(),
            'budget_annee' => $budgetAnnee,
            'lignes_budgetaires' => $lignes,
        ];
    }

    private function serializeLigne(LigneBudgetaire $ligne, Budget $budget): array
    {
        $alloue = (float) $ligne->getMontantAlloue();
        $utilise = (float) $ligne->getMontantUtilise();

        return [
            'id' => $ligne->getId(),
            'code' => $ligne->getCode(),
            'libelle' => $ligne->getLibelle(),
            'montant_alloue' => $alloue,
            'montant_utilise' => $utilise,
            'montant_disponible' => $alloue - $utilise,
            'budget_id' => $budget->getId(),
            'budget_libelle' => $budget->getLibelle(),
            'annee' => $budget->getAnnee(),
        ];
    }
}
