<?php

namespace App\Tests\Service;

use App\Service\TokenService;
use Psr\Cache\CacheItemInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\CacheInterface;

class TokenServiceTest extends KernelTestCase
{
    private CacheInterface $cache;
    private TokenService $service;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->cache = self::getContainer()->get('cache.app');

        $this->cache->delete(TokenService::KEY_NAME);

        $this->service = new TokenService($this->cache);
    }

    public function testGeneratesTokenOnFirstCall(): void
    {
        $first = $this->service->getLongLivedToken();

        self::assertIsArray($first);
        self::assertArrayHasKey('token', $first);
        self::assertArrayHasKey('ttl', $first);
        self::assertNotEmpty($first['token']);
        self::assertStringStartsWith('wtt', $first['token']);
        self::assertGreaterThan(0, $first['ttl']);

        $second = $this->service->getLongLivedToken();
        self::assertIsArray($second);
        self::assertSame($first['token'], $second['token']);
        self::assertLessThanOrEqual(floor($first['ttl']), floor($second['ttl']));
    }

    public function testRefreshesWhenExpiringSoon(): void
    {
        $initial = $this->service->getLongLivedToken();
        $initialToken = $initial['token'];

        $this->cache->delete(TokenService::KEY_NAME);
        $this->cache->get(TokenService::KEY_NAME, function (CacheItemInterface $item) use ($initialToken) {
            $item->set($initialToken);
            $item->expiresAfter(TokenService::MIN_SECONDS - 5);
            return $initialToken;
        });

        $new = $this->service->getLongLivedToken();
        self::assertIsArray($new);
        self::assertArrayHasKey('token', $new);
        self::assertNotSame($initialToken, $new['token']);
        self::assertGreaterThan(TokenService::MIN_SECONDS, $new['ttl']);
    }
}
