<?php

namespace App\Controller\Trait;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait
{
    protected function success(mixed $data = null, int $status = Response::HTTP_OK): JsonResponse
    {
        $payload = ['success' => true];

        if ($data !== null) {
            if (is_array($data) && array_is_list($data)) {
                $payload['data'] = $data;
            } elseif (is_array($data)) {
                $payload = array_merge($payload, $data);
            } else {
                $payload['data'] = $data;
            }
        }

        return $this->json($payload, $status);
    }

    protected function error(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    protected function decodeJson(Request $request): ?array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : null;
    }
}
