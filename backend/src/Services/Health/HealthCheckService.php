<?php

declare(strict_types=1);

namespace App\Services\Health;

use Doctrine\DBAL\Connection;

final readonly class HealthCheckService
{
    public function __construct(
        private Connection $connection,
        private string $rabbitMqHost,
        private int $rabbitMqPort,
    ) {
    }

    /**
     * @return array{status:string, db:bool, rabbitmq:bool}
     */
    public function check(): array
    {
        $db = $this->checkDb();
        $rabbit = $this->checkRabbitMq();

        return [
            'status' => $db && $rabbit ? 'ok' : 'degraded',
            'db' => $db,
            'rabbitmq' => $rabbit,
        ];
    }

    private function checkDb(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkRabbitMq(): bool
    {
        $connection = @\fsockopen($this->rabbitMqHost, $this->rabbitMqPort, $errno, $errstr, 2);

        if ($connection === false) {
            return false;
        }

        \fclose($connection);

        return true;
    }
}
