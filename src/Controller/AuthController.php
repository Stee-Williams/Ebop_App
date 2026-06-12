<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
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
    UserPasswordHasherInterface $passwordHasher,
    ApiTokenRepository $apiTokenRepository,
    EntityManagerInterface $em,
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

    $tokenValue = bin2hex(random_bytes(32));

    foreach ($apiTokenRepository->findBy(['user' => $user]) as $oldToken) {
        $em->remove($oldToken);
    }

    $apiToken = new ApiToken();
    $apiToken->setToken($tokenValue);
    $apiToken->setUser($user);
    $em->persist($apiToken);
    $em->flush();

    return $this->json([
        'success' => true,
        'message' => 'Connexion réussie',
        'token' => $tokenValue,
        'user' => [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'matricule' => $user->getMatricule(),
            'role' => $user->getRole()?->getNom(),
            'province_id' => $user->getProvince()?->getId(),
            'province_nom' => $user->getProvince()?->getNom(),
        ]
    ]);
}


    // =========================
    // FORGOT PASSWORD
    // =========================
    #[Route('/api/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $matricule = trim($data['matricule'] ?? '');
        $newPassword = $data['new_password'] ?? null;

        if (!$matricule || !$newPassword) {
            return $this->json([
                'success' => false,
                'message' => 'Matricule et nouveau mot de passe requis',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($newPassword) < 6) {
            return $this->json([
                'success' => false,
                'message' => 'Le mot de passe doit contenir au moins 6 caractères',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['matricule' => $matricule]);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($passwordHasher->isPasswordValid($user, $newPassword)) {
            return $this->json([
                'success' => false,
                'message' => 'Le nouveau mot de passe ne peut pas être identique à l\'ancien',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour avec succès',
        ]);
    }
}