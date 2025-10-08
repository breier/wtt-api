<?php

namespace App\Controller;

use App\Entity\Visit;
use App\Repository\VisitRepository;
use App\Service\TokenService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

final class VisitController extends AbstractController
{
    public function __construct(private readonly VisitRepository $visitRepository) {}

    #[Route('/api/visit', name: 'api_visit', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $pageSize = (int) $request->query->get('pageSize', 25);

        $visitsWithCount = $this->visitRepository->findByParams(
            $request->query->all(),
            ($page - 1) * $pageSize,
            $pageSize,
        );

        return $this->json($visitsWithCount);
    }

    #[Route('/api/visit', name: 'api_visit_create', methods: ['POST'])]
    public function create(
        Request $request,
        TokenService $tokenService,
        RateLimiterFactoryInterface $visitPerIpLimiter,
        LoggerInterface $logger,
    ): JsonResponse {
        $limiter = $visitPerIpLimiter->create($request->getClientIp() ?? 'unknown');
        $limit = $limiter->consume(1);
        if (! $limit->isAccepted()) {
            return $this->json(null, JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $payload = json_decode($request->getContent(), true, 2, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $logger->warning('Invalid JSON payload for creating a new visit.', ['exception' => $e]);

            return $this->json(null, 200);
        }

        if ($tokenService->getLongLivedToken()['token'] !== ($payload['token'] ?? null)) {
            $logger->warning('Invalid token provided for creating a new visit.', ['payload' => $payload]);

            return $this->json(null, 200);
        }

        if (empty($payload['request_url']) || empty($payload['fp_hash'])) {
            $logger->warning('Missing required fields for creating a new visit.', ['payload' => $payload]);

            return $this->json(null, 200);
        }

        unset($payload['token']);

        $visit = Visit::fromArray($payload);

        $existingVisit = $this->visitRepository
            ->findOneTodayByRequestUrlAndFingerprint($visit->request_url, $visit->fp_hash);

        if (empty($existingVisit)) {
            $this->visitRepository->save($visit);
        }

        return $this->json(null, 200);
    }
}
