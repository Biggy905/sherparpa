<?php

declare(strict_types=1);

namespace Tests\E2E;

final class HealthTest extends E2ETestCase
{
    public function testHealthReturnsOk(): void
    {
        $response = $this->get('/api/health');

        self::assertSame(200, $response->getStatusCode());
        $data = $this->json($response);
        self::assertTrue($data['db']);
        self::assertArrayHasKey('rabbitmq', $data);
    }
}
