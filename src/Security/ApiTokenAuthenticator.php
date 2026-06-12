<?php

namespace App\Security;

use App\Repository\ApiTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/api')
            && !in_array($request->getPathInfo(), ['/api/login', '/api/forgot-password'], true);
    }

    public function authenticate(Request $request): Passport
    {
        $authorization = $request->headers->get('Authorization', '');

        if (!str_starts_with($authorization, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException('Token d\'authentification requis');
        }

        $token = trim(substr($authorization, 7));
        if ($token === '') {
            throw new CustomUserMessageAuthenticationException('Token d\'authentification invalide');
        }

        $apiToken = $this->apiTokenRepository->findValidToken($token);
        if ($apiToken === null) {
            throw new CustomUserMessageAuthenticationException('Session expirée ou token invalide');
        }

        $user = $apiToken->getUser();
        if ($user === null) {
            throw new CustomUserMessageAuthenticationException('Utilisateur introuvable');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn () => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->unauthorizedResponse($exception->getMessage());
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->unauthorizedResponse(
            $authException?->getMessage() ?? 'Authentification requise'
        );
    }

    private function unauthorizedResponse(string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
