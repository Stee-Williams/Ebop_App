<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
final class UserController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findBy([], ['nom' => 'ASC']);

        return $this->success(array_map(fn (User $u) => $this->serialize($u), $users));
    }

    #[Route('/matricule/{matricule}', name: 'api_get_user_by_matricule', methods: ['GET'])]
    public function getByMatricule(
        string $matricule,
        UserRepository $userRepository
    ): JsonResponse {
        $user = $userRepository->findOneBy(['matricule' => $matricule]);

        if (!$user) {
            return $this->error('Aucun utilisateur trouvé', Response::HTTP_NOT_FOUND);
        }

        return $this->success(['user' => $this->serialize($user)]);
    }

    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->error('Utilisateur introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->success($this->serialize($user));
    }

    #[Route('/{matricule}/role', name: 'api_update_user_role', methods: ['PATCH', 'PUT'])]
    public function updateRole(
        string $matricule,
        Request $request,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = $this->decodeJson($request);
        $roleId = $data['role_id'] ?? null;

        if (!$roleId) {
            return $this->error('Le rôle est obligatoire');
        }

        $user = $userRepository->findOneBy(['matricule' => $matricule]);

        if (!$user) {
            return $this->error('Utilisateur introuvable', Response::HTTP_NOT_FOUND);
        }

        $role = $roleRepository->find($roleId);

        if (!$role) {
            return $this->error('Rôle introuvable', Response::HTTP_NOT_FOUND);
        }

        $user->setRole($role);
        $em->flush();

        return $this->success([
            'message' => 'Rôle mis à jour avec succès',
            'user' => $this->serialize($user),
        ]);
    }

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->error('Utilisateur introuvable', Response::HTTP_NOT_FOUND);
        }

        $em->remove($user);
        $em->flush();

        return $this->success(['message' => 'Utilisateur supprimé']);
    }

    private function serialize(User $user): array
    {
        return [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'matricule' => $user->getMatricule(),
            'role' => $user->getRole()?->getNom(),
            'role_id' => $user->getRole()?->getId(),
        ];
    }
}
