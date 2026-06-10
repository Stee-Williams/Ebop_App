<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\Fournisseur;
use App\Repository\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fournisseurs')]
final class FournisseurController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_fournisseurs_list', methods: ['GET'])]
    public function list(FournisseurRepository $repository): JsonResponse
    {
        $items = $repository->findBy([], ['nom' => 'ASC']);

        return $this->success(array_map(fn (Fournisseur $f) => $this->serialize($f), $items));
    }

    #[Route('/{id}', name: 'api_fournisseurs_show', methods: ['GET'])]
    public function show(int $id, FournisseurRepository $repository): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Fournisseur introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->success($this->serialize($item));
    }

    #[Route('', name: 'api_fournisseurs_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['nom'])) {
            return $this->error('Le nom est obligatoire');
        }

        $item = new Fournisseur();
        $item->setNom($data['nom']);
        $item->setAdresse($data['adresse'] ?? null);
        $item->setTelephone($data['telephone'] ?? null);
        $item->setNif($data['nif'] ?? null);
        $em->persist($item);
        $em->flush();

        return $this->success(['message' => 'Fournisseur créé', 'data' => $this->serialize($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_fournisseurs_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        FournisseurRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Fournisseur introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if (isset($data['nom'])) {
            $item->setNom($data['nom']);
        }
        if (array_key_exists('adresse', $data)) {
            $item->setAdresse($data['adresse']);
        }
        if (array_key_exists('telephone', $data)) {
            $item->setTelephone($data['telephone']);
        }
        if (array_key_exists('nif', $data)) {
            $item->setNif($data['nif']);
        }

        $em->flush();

        return $this->success(['message' => 'Fournisseur mis à jour', 'data' => $this->serialize($item)]);
    }

    #[Route('/{id}', name: 'api_fournisseurs_delete', methods: ['DELETE'])]
    public function delete(int $id, FournisseurRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Fournisseur introuvable', Response::HTTP_NOT_FOUND);
        }

        $em->remove($item);
        $em->flush();

        return $this->success(['message' => 'Fournisseur supprimé']);
    }

    private function serialize(Fournisseur $item): array
    {
        return [
            'id' => $item->getId(),
            'nom' => $item->getNom(),
            'adresse' => $item->getAdresse(),
            'telephone' => $item->getTelephone(),
            'nif' => $item->getNif(),
        ];
    }
}
