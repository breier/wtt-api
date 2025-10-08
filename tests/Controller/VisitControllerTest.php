<?php

namespace App\Tests\Controller;

use App\Entity\Visit;
use App\Repository\VisitRepository;
use App\Service\TokenService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class VisitControllerTest extends WebTestCase
{
    private string $token;
    private KernelBrowser $client;
    private MockObject|VisitRepository $repo;

    private string $remoteIp = '0.0.0.0';

    protected function setUp(): void
    {
        self::ensureKernelShutdown();

        $this->client = static::createClient();

        $this->token = $this->client->getContainer()->get(TokenService::class)->getLongLivedToken()['token'];

        $this->repo = $this->createMock(VisitRepository::class);

        $this->client->getContainer()->set(VisitRepository::class, $this->repo);

        $this->remoteIp = long2ip(mt_rand());
    }

    public function testCreateNewVisit(): void
    {
        $payload = [
            'request_url' => 'https://example.com/some/path?x=1',
            'fp_hash' => hash('sha512', 'fingerprint-123'),
            'client_ts' => time(),
            'token' => $this->token,
        ];

        $this->repo->expects(self::once())
            ->method('findOneTodayByRequestUrlAndFingerprint')
            ->with('https://example.com/some/path?x=1', $payload['fp_hash'])
            ->willReturn(null);

        $this->repo->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(Visit::class));

        $this->client->request(
            method: 'POST',
            uri: '/api/visit',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateExistingVisit(): void
    {
        $payload = [
            'request_url' => 'https://example.com/some/path?x=1',
            'fp_hash' => hash('sha512', 'fingerprint-123'),
            'client_ts' => time(),
            'token' => $this->token,
        ];

        $this->repo->expects(self::once())
            ->method('findOneTodayByRequestUrlAndFingerprint')
            ->with($payload['request_url'], $payload['fp_hash'])
            ->willReturn(new Visit());

        $this->repo->expects(self::never())->method('save');

        $this->client->request(
            method: 'POST',
            uri: '/api/visit',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testInvalidToken(): void
    {
        $payload = [
            'request_url' => 'https://example.com/another',
            'fp_hash' => hash('sha512', 'fingerprint-123'),
            'client_ts' => time(),
            'token' => 'invalid-token',
        ];

        $this->repo->expects(self::never())->method('findOneTodayByRequestUrlAndFingerprint');
        $this->repo->expects(self::never())->method('save');

        $this->client->request(
            method: 'POST',
            uri: '/api/visit',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testIndex(): void
    {
        $visit1 = Visit::fromArray([
            'request_url' => 'https://example.com/a',
            'fp_hash' => 'hash1',
            'client_ts' => time(),
        ]);

        $visit2 = Visit::fromArray([
            'request_url' => 'https://example.com/b',
            'fp_hash' => 'hash2',
            'client_ts' => time(),
        ]);

        $this->repo->expects(self::once())
            ->method('findByParams')
            ->with([], 0, 25)
            ->willReturn([
                'items' => [$visit1, $visit2],
                'total' => 2,
            ]);

        $this->client->request('GET', '/api/visit');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('total', $data);

        self::assertCount(2, $data['items']);
        self::assertSame($visit1->request_url, $data['items'][0]['request_url'] ?? null);
        self::assertSame($visit1->fp_hash, $data['items'][0]['fp_hash'] ?? null);
        self::assertSame($visit2->request_url, $data['items'][1]['request_url'] ?? null);
        self::assertSame($visit2->fp_hash, $data['items'][1]['fp_hash'] ?? null);
    }

    public function testRateLimitExceeded(): void
    {
        // Keep the same kernel/container (and thus the mocked repository) across multiple requests
        // so we can simulate hitting the rate limit without losing the mock after a reboot.
        $this->client->disableReboot();

        $this->repo->expects(self::any())->method('findOneTodayByRequestUrlAndFingerprint')->willReturn(null);
        $this->repo->expects(self::any())->method('save');

        $payload = [
            'request_url' => 'https://example.com/rate-test',
            'fp_hash' => hash('sha512', 'fp-rate-test'),
            'client_ts' => time(),
            'token' => $this->token,
        ];

        $configuredLimit = 50; // see framework.yaml rate_limiter.visit_per_ip.limit
        for ($i = 1; $i <= $configuredLimit; $i++) {
            $this->client->request(
                method: 'POST',
                uri: '/api/visit',
                server: ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $this->remoteIp],
                content: json_encode($payload),
            );

            self::assertSame(200, $this->client->getResponse()->getStatusCode());
        }

        $this->client->request(
            method: 'POST',
            uri: '/api/visit',
            server: ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $this->remoteIp],
            content: json_encode($payload),
        );

        self::assertSame(429, $this->client->getResponse()->getStatusCode());
    }
}
