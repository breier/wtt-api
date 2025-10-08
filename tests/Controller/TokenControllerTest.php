<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TokenControllerTest extends WebTestCase
{
    public function testGetToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/token');

        self::assertSame(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertIsArray($data);
        self::assertArrayHasKey('token', $data);
        self::assertArrayHasKey('ttl', $data);
    }
}
