<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\Administration;
use App\Repository\AdministrationRepository;
use App\Repository\ProvinceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

        return $this->success($this->serialize($item));
    }

    #[Route('', name: 'api_administrations_create', methods: ['POST'])]
    public function create(
        Request $request,
        ProvinceRepository $provinceRepository,
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
        $em->flush();

        return $this->success(['message' => 'Administration créée', 'data' => $this->serialize($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_administrations_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        AdministrationRepository $repository,
        ProvinceRepository $provinceRepository,
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

        $em->flush();

        return $this->success(['message' => 'Administration mise à jour', 'data' => $this->serialize($item)]);
    }

    #[Route('/{id}', name: 'api_administrations_delete', methods: ['DELETE'])]
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

    private function serialize(Administration $item): array
    {
        return [
            'id' => $item->getId(),
            'nom' => $item->getNom(),
            'code' => $item->getCode(),
            'province_id' => $item->getProvince()?->getId(),
            'province_nom' => $item->getProvince()?->getNom(),
        ];
    }
}
