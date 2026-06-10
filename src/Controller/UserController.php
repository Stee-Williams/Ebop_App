<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/api/users/matricule/{matricule}', name: 'api_get_user_by_matricule', methods: ['GET'])]
    public function getByMatricule(
        string $matricule,
        UserRepository $userRepository
    ): JsonResponse {
        $user = $userRepository->findByMatricule($matricule);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun utilisateur trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'matricule' => $user->getMatricule(),
                'role' => $user->getRole()?->getNom(),
                'role_id' => $user->getRole()?->getId(),
            ]
        ]);
    }

    #[Route('/api/users/{matricule}/role', name: 'api_update_user_role', methods: ['PATCH', 'PUT'])]
    public function updateRole(
        string $matricule,
        Request $request,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $roleId = $data['role_id'] ?? null;

        if (!$roleId) {
            return $this->json([
                'success' => false,
                'message' => 'Le rôle est obligatoire'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findByMatricule($matricule);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        $role = $roleRepository->find($roleId);

        if (!$role) {
            return $this->json([
                'success' => false,
                'message' => 'Rôle introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        $user->setRole($role);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Rôle mis à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'matricule' => $user->getMatricule(),
                'role' => $role->getNom(),
                'role_id' => $role->getId(),
            ]
        ]);
    }
}
