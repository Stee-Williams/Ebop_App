<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\User;
use App\Repository\ProvinceRepository;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        ProvinceRepository $provinceRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = $this->decodeJson($request);

        $nom = trim($data['nom'] ?? '');
        $matricule = trim($data['matricule'] ?? '');
        $password = $data['password'] ?? null;
        $roleId = $data['role_id'] ?? null;
        $provinceId = $data['province_id'] ?? null;

        if ($nom === '' || $matricule === '' || !$password || !$roleId || !$provinceId) {
            return $this->error('Tous les champs sont obligatoires, y compris la province');
        }

        if ($userRepository->findOneBy(['matricule' => $matricule])) {
            return $this->error('Ce matricule est déjà utilisé', Response::HTTP_CONFLICT);
        }

        $role = $roleRepository->find($roleId);
        if (!$role) {
            return $this->error('Rôle introuvable', Response::HTTP_NOT_FOUND);
        }

        $province = $provinceRepository->find($provinceId);
        if (!$province) {
            return $this->error('Province introuvable', Response::HTTP_NOT_FOUND);
        }

        $user = new User();
        $user->setNom($nom);
        $user->setMatricule($matricule);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRole($role);
        $user->setProvince($province);

        try {
            $em->persist($user);
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            if (str_contains($e->getMessage(), 'matricule')) {
                return $this->error('Ce matricule est déjà utilisé', Response::HTTP_CONFLICT);
            }

            return $this->error(
                'Erreur interne : identifiant utilisateur en conflit. Exécutez : php bin/console doctrine:migrations:migrate',
                Response::HTTP_CONFLICT
            );
        }

        return $this->success([
            'message' => 'Utilisateur créé avec succès',
            'user' => $this->serialize($user),
        ], Response::HTTP_CREATED);
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

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        ProvinceRepository $provinceRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->error('Utilisateur introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);

        if (isset($data['nom']) && trim($data['nom']) !== '') {
            $user->setNom(trim($data['nom']));
        }

        if (isset($data['role_id'])) {
            $role = $roleRepository->find($data['role_id']);
            if (!$role) {
                return $this->error('Rôle introuvable', Response::HTTP_NOT_FOUND);
            }
            $user->setRole($role);
        }

        if (array_key_exists('province_id', $data)) {
            if ($data['province_id'] === null) {
                $user->setProvince(null);
            } else {
                $province = $provinceRepository->find($data['province_id']);
                if (!$province) {
                    return $this->error('Province introuvable', Response::HTTP_NOT_FOUND);
                }
                $user->setProvince($province);
            }
        }

        $em->flush();

        return $this->success([
            'message' => 'Utilisateur mis à jour',
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
            'province_id' => $user->getProvince()?->getId(),
            'province_nom' => $user->getProvince()?->getNom(),
        ];
    }
}
