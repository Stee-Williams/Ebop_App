<?php

namespace App\Controller;

use App\Entity\User; 
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthController extends AbstractController
{
    // =========================
    // LOGIN
    // =========================
   #[Route('/api/login', name: 'api_login', methods: ['POST'])]
public function login(
    Request $request,
    UserRepository $userRepository,
    UserPasswordHasherInterface $passwordHasher
): JsonResponse {

    $data = json_decode($request->getContent(), true);

    $matricule = $data['matricule'] ?? null;
    $password = $data['password'] ?? null;

    if (!$matricule || !$password) {
        return $this->json([
            'success' => false,
            'message' => 'Matricule et mot de passe requis'
        ], Response::HTTP_BAD_REQUEST);
    }

    $user = $userRepository->findOneBy([
        'matricule' => $matricule
    ]);

    if (!$user) {
        return $this->json([
            'success' => false,
            'message' => 'Utilisateur introuvable'
        ], Response::HTTP_NOT_FOUND);
    }

    if (!$passwordHasher->isPasswordValid($user, $password)) {
        return $this->json([
            'success' => false,
            'message' => 'Mot de passe incorrect'
        ], Response::HTTP_UNAUTHORIZED);
    }

    $token = bin2hex(random_bytes(32));

    return $this->json([
        'success' => true,
        'message' => 'Connexion réussie',
        'token' => $token,
        'user' => [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'matricule' => $user->getMatricule(),
            'role' => $user->getRole()?->getNom()
        ]
    ]);
}


    // =========================
    // CREATE USER
    // =========================
    #[Route('/api/users', name: 'api_create_user', methods: ['POST'])]
    public function createUser(
        Request $request,
        EntityManagerInterface $em,
        RoleRepository $roleRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        $nom = $data['nom'] ?? null;
        $matricule = $data['matricule'] ?? null;
        $password = $data['password'] ?? null;
        $roleId = $data['role_id'] ?? null;

        if (!$nom || !$matricule || !$password || !$roleId) {
            return $this->json([
                'success' => false,
                'message' => 'Tous les champs sont obligatoires'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si matricule existe déjà
        $existingUser = $em->getRepository(User::class)
            ->findOneBy(['matricule' => $matricule]);

        if ($existingUser) {
            return $this->json([
                'success' => false,
                'message' => 'Ce matricule est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        // Récupération du rôle
        $role = $roleRepository->find($roleId);

        if (!$role) {
            return $this->json([
                'success' => false,
                'message' => 'Rôle introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        // Création utilisateur
        $user = new User();
        $user->setNom($nom);
        $user->setMatricule($matricule);

        //  HASH PASSWORD (IMPORTANT)
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $user->setRole($role);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'matricule' => $user->getMatricule(),
                'role' => $role->getNom()
            ]
        ], Response::HTTP_CREATED);
    }
}