<?php

namespace App\Controller;

use App\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class TokenController extends AbstractController
{
    #[Route('/api/token', name: 'api_token', methods: ['GET'])]
    public function index(TokenService $tokenService): JsonResponse
    {
        return $this->json($tokenService->getLongLivedToken());
    }
}
