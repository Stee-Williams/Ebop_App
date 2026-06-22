<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\Province;
use App\Repository\ProvinceRepository;
use App\Security\ProvinceScopeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/provinces')]
final class ProvinceController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_provinces_list', methods: ['GET'])]
    public function list(ProvinceRepository $repository, ProvinceScopeService $provinceScope): JsonResponse
    {
        $provinces = $repository->findBy([], ['nom' => 'ASC']);
        $provinces = $provinceScope->filterByProvince(
            $provinces,
            static fn (Province $p) => $p->getId()
        );

        return $this->success(array_map(fn (Province $p) => $this->serialize($p), $provinces));
    }

    #[Route('/{id}', name: 'api_provinces_show', methods: ['GET'])]
    public function show(int $id, ProvinceRepository $repository, ProvinceScopeService $provinceScope): JsonResponse
    {
        $province = $repository->find($id);
        if (!$province) {
            return $this->error('Province introuvable', Response::HTTP_NOT_FOUND);
        }

        $provinceScope->assertCanAccessProvinceId($province->getId());

        return $this->success($this->serialize($province));
    }

    #[Route('', name: 'api_provinces_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['nom']) || empty($data['code'])) {
            return $this->error('Le nom et le code sont obligatoires');
        }

        $province = new Province();
        $province->setNom($data['nom']);
        $province->setCode($data['code']);
        $em->persist($province);
        $em->flush();

        return $this->success(['message' => 'Province créée', 'data' => $this->serialize($province)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_provinces_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, ProvinceRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $province = $repository->find($id);
        if (!$province) {
            return $this->error('Province introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if (isset($data['nom'])) {
            $province->setNom($data['nom']);
        }
        if (isset($data['code'])) {
            $province->setCode($data['code']);
        }

        $em->flush();

        return $this->success(['message' => 'Province mise à jour', 'data' => $this->serialize($province)]);
    }

    #[Route('/{id}', name: 'api_provinces_delete', methods: ['DELETE'])]
    public function delete(int $id, ProvinceRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $province = $repository->find($id);
        if (!$province) {
            return $this->error('Province introuvable', Response::HTTP_NOT_FOUND);
        }

        $em->remove($province);
        $em->flush();

        return $this->success(['message' => 'Province supprimée']);
    }

    private function serialize(Province $province): array
    {
        return [
            'id' => $province->getId(),
            'nom' => $province->getNom(),
            'code' => $province->getCode(),
        ];
    }
}
