<?php

declare(strict_types=1);

namespace App\Factories;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final readonly class LoggerFactory
{
    public function __construct(
        private string $logPath,
    ) {
    }

    public function create(): LoggerInterface
    {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($this->logPath, Level::Debug));

        return $logger;
    }
}
