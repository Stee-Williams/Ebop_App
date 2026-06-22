<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\PosteComptable;
use App\Repository\PosteComptableRepository;
use App\Repository\ProvinceRepository;
use App\Security\ProvinceScopeService;
use App\Security\Voter\PermissionVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/postes-comptables')]
final class PosteComptableController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_postes_comptables_list', methods: ['GET'])]
    public function list(PosteComptableRepository $repository, ProvinceScopeService $provinceScope): JsonResponse
    {
        $items = $repository->findBy([], ['libelle' => 'ASC']);
        $items = $provinceScope->filterByProvince(
            $items,
            fn (PosteComptable $p) => $provinceScope->getProvinceIdFromPoste($p)
        );

        return $this->success(array_map(fn (PosteComptable $p) => $this->serialize($p), $items));
    }

    #[Route('/{id}', name: 'api_postes_comptables_show', methods: ['GET'])]
    public function show(int $id, PosteComptableRepository $repository, ProvinceScopeService $provinceScope): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Poste comptable introuvable', Response::HTTP_NOT_FOUND);
        }

        $provinceScope->assertCanAccessPoste($item);

        return $this->success($this->serialize($item));
    }

    #[Route('', name: 'api_postes_comptables_create', methods: ['POST'])]
    #[IsGranted(PermissionVoter::MANAGE_ADMINISTRATIONS)]
    public function create(
        Request $request,
        ProvinceRepository $provinceRepository,
        EntityManagerInterface $em,
        ProvinceScopeService $provinceScope,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['libelle'])) {
            return $this->error('Le libellé est obligatoire');
        }

        $item = new PosteComptable();
        $item->setLibelle($data['libelle']);
        $item->setCode($data['code'] ?? null);
        $item->setDescription($data['description'] ?? null);
        $item->setType($data['type'] ?? null);

        if (!empty($data['province_id']) || $provinceScope->getRestrictedProvinceId() !== null) {
            $provinceId = $provinceScope->resolveProvinceIdForCreate(
                !empty($data['province_id']) ? (int) $data['province_id'] : null
            );
            if ($provinceId === null) {
                return $this->error('La province est obligatoire');
            }
            $province = $provinceRepository->find($provinceId);
            if (!$province) {
                return $this->error('Province introuvable', Response::HTTP_NOT_FOUND);
            }
            $item->setProvince($province);
        }

        $em->persist($item);
        $em->flush();

        return $this->success(['message' => 'Poste comptable créé', 'data' => $this->serialize($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_postes_comptables_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(PermissionVoter::MANAGE_ADMINISTRATIONS)]
    public function update(
        int $id,
        Request $request,
        PosteComptableRepository $repository,
        ProvinceRepository $provinceRepository,
        EntityManagerInterface $em,
        ProvinceScopeService $provinceScope,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Poste comptable introuvable', Response::HTTP_NOT_FOUND);
        }

        $provinceScope->assertCanAccessPoste($item);

        $data = $this->decodeJson($request);
        if (isset($data['libelle'])) {
            $item->setLibelle($data['libelle']);
        }
        if (array_key_exists('code', $data)) {
            $item->setCode($data['code']);
        }
        if (array_key_exists('description', $data)) {
            $item->setDescription($data['description']);
        }
        if (array_key_exists('type', $data)) {
            $item->setType($data['type']);
        }
        if (array_key_exists('province_id', $data)) {
            $provinceId = $provinceScope->resolveProvinceIdForCreate(
                $data['province_id'] === null ? null : (int) $data['province_id']
            );
            if ($provinceId === null) {
                $item->setProvince(null);
            } else {
                $province = $provinceRepository->find($provinceId);
                if (!$province) {
                    return $this->error('Province introuvable', Response::HTTP_NOT_FOUND);
                }
                $item->setProvince($province);
            }
        }

        $em->flush();

        return $this->success(['message' => 'Poste comptable mis à jour', 'data' => $this->serialize($item)]);
    }

    #[Route('/{id}', name: 'api_postes_comptables_delete', methods: ['DELETE'])]
    #[IsGranted(PermissionVoter::MANAGE_ADMINISTRATIONS)]
    public function delete(int $id, PosteComptableRepository $repository, EntityManagerInterface $em, ProvinceScopeService $provinceScope): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Poste comptable introuvable', Response::HTTP_NOT_FOUND);
        }

        $provinceScope->assertCanAccessPoste($item);

        $em->remove($item);
        $em->flush();

        return $this->success(['message' => 'Poste comptable supprimé']);
    }

    private function serialize(PosteComptable $item): array
    {
        return [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'libelle' => $item->getLibelle(),
            'description' => $item->getDescription(),
            'type' => $item->getType(),
            'province_id' => $item->getProvince()?->getId(),
            'province_nom' => $item->getProvince()?->getNom(),
        ];
    }
}
