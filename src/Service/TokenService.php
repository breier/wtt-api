<?php

namespace App\Service;

use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

class TokenService
{
    public const TTL_SECONDS = 21600; // 6 hours
    public const MIN_SECONDS = 10;
    public const KEY_NAME = 'wtt_token';

    public function __construct(private CacheInterface $cacheService) { }

    public function getLongLivedToken(): array
    {
        $token = $this->getToken($metadata);

        $remainingTtl = $metadata['ttl'] ?? (
            !empty($metadata['expiry']) ? $metadata['expiry'] - time() : null
        );

        if ($remainingTtl !== null && $remainingTtl < self::MIN_SECONDS) {
            $this->cacheService->delete(self::KEY_NAME);

            $token = $this->getToken($metadata);

            $remainingTtl = $metadata['ttl'] ?? (
                !empty($metadata['expiry']) ? $metadata['expiry'] - time() : null
            );
        }

        return [
            'token' => $token,
            'ttl' => $remainingTtl,
        ];
    }

    private function getToken(?array &$metadata = null): string
    {
        return $this->cacheService->get(self::KEY_NAME, function (CacheItemInterface $item) {
            $newToken = uniqid('wtt', true);

            $item->set($newToken);
            $item->expiresAfter(self::TTL_SECONDS);

            return $newToken;
        }, null, $metadata);
    }
}