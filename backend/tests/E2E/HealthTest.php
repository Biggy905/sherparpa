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
        self::assertSame('ok', $data['status']);
        self::assertTrue($data['db']);
        // rabbitmq — false, т.к. в тестах TCP-коннект к rabbitmq не поднят,
        // но health всё равно отдаёт 200. Проверим только db.
    }
}
