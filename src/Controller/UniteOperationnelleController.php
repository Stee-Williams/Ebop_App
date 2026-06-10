<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\UniteOperationnelle;
use App\Repository\AdministrationRepository;
use App\Repository\UniteOperationnelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/unites-operationnelles')]
final class UniteOperationnelleController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_unites_operationnelles_list', methods: ['GET'])]
    public function list(UniteOperationnelleRepository $repository): JsonResponse
    {
        $items = $repository->findBy([], ['nom' => 'ASC']);

        return $this->success(array_map(fn (UniteOperationnelle $u) => $this->serialize($u), $items));
    }

    #[Route('/{id}', name: 'api_unites_operationnelles_show', methods: ['GET'])]
    public function show(int $id, UniteOperationnelleRepository $repository): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Unité opérationnelle introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->success($this->serialize($item));
    }

    #[Route('', name: 'api_unites_operationnelles_create', methods: ['POST'])]
    public function create(
        Request $request,
        AdministrationRepository $administrationRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['nom'])) {
            return $this->error('Le nom est obligatoire');
        }

        $item = new UniteOperationnelle();
        $item->setNom($data['nom']);
        $item->setCode($data['code'] ?? null);

        if (!empty($data['administration_id'])) {
            $administration = $administrationRepository->find($data['administration_id']);
            if (!$administration) {
                return $this->error('Administration introuvable', Response::HTTP_NOT_FOUND);
            }
            $item->setAdministration($administration);
        }

        $em->persist($item);
        $em->flush();

        return $this->success(['message' => 'Unité opérationnelle créée', 'data' => $this->serialize($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_unites_operationnelles_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        UniteOperationnelleRepository $repository,
        AdministrationRepository $administrationRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Unité opérationnelle introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if (isset($data['nom'])) {
            $item->setNom($data['nom']);
        }
        if (array_key_exists('code', $data)) {
            $item->setCode($data['code']);
        }
        if (array_key_exists('administration_id', $data)) {
            if ($data['administration_id'] === null) {
                $item->setAdministration(null);
            } else {
                $administration = $administrationRepository->find($data['administration_id']);
                if (!$administration) {
                    return $this->error('Administration introuvable', Response::HTTP_NOT_FOUND);
                }
                $item->setAdministration($administration);
            }
        }

        $em->flush();

        return $this->success(['message' => 'Unité opérationnelle mise à jour', 'data' => $this->serialize($item)]);
    }

    #[Route('/{id}', name: 'api_unites_operationnelles_delete', methods: ['DELETE'])]
    public function delete(int $id, UniteOperationnelleRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Unité opérationnelle introuvable', Response::HTTP_NOT_FOUND);
        }

        $em->remove($item);
        $em->flush();

        return $this->success(['message' => 'Unité opérationnelle supprimée']);
    }

    private function serialize(UniteOperationnelle $item): array
    {
        return [
            'id' => $item->getId(),
            'nom' => $item->getNom(),
            'code' => $item->getCode(),
            'administration_id' => $item->getAdministration()?->getId(),
            'administration_nom' => $item->getAdministration()?->getNom(),
        ];
    }
}
