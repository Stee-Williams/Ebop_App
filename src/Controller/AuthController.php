<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    #[Route('/auth', name: 'app_auth')]
    public function index(): Response
    {
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }


    #[Route('/test', name: 'app_test')]
    public function test(): Response
    {
        return $this->json([ 
            'message' => 'Ceci est une route de test',
        ]);
    }
}
