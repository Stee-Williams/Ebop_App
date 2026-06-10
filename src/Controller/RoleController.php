<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/roles')]
final class RoleController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_roles_list', methods: ['GET'])]
    public function list(RoleRepository $repository): JsonResponse
    {
        $roles = $repository->findBy([], ['nom' => 'ASC']);

        return $this->success(array_map(fn (Role $r) => $this->serialize($r), $roles));
    }

    #[Route('/{id}', name: 'api_roles_show', methods: ['GET'])]
    public function show(int $id, RoleRepository $repository): JsonResponse
    {
        $role = $repository->find($id);
        if (!$role) {
            return $this->error('Rôle introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->success($this->serialize($role));
    }

    #[Route('', name: 'api_roles_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['nom'])) {
            return $this->error('Le nom est obligatoire');
        }

        $role = new Role();
        $role->setNom($data['nom']);
        $em->persist($role);
        $em->flush();

        return $this->success(['message' => 'Rôle créé', 'data' => $this->serialize($role)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_roles_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, RoleRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $role = $repository->find($id);
        if (!$role) {
            return $this->error('Rôle introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if (isset($data['nom'])) {
            $role->setNom($data['nom']);
        }

        $em->flush();

        return $this->success(['message' => 'Rôle mis à jour', 'data' => $this->serialize($role)]);
    }

    #[Route('/{id}', name: 'api_roles_delete', methods: ['DELETE'])]
    public function delete(int $id, RoleRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $role = $repository->find($id);
        if (!$role) {
            return $this->error('Rôle introuvable', Response::HTTP_NOT_FOUND);
        }

        $em->remove($role);
        $em->flush();

        return $this->success(['message' => 'Rôle supprimé']);
    }

    private function serialize(Role $role): array
    {
        return [
            'id' => $role->getId(),
            'nom' => $role->getNom(),
        ];
    }
}
