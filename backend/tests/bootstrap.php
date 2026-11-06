<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// .env лежит на уровень выше /app/backend → /app/.env
$envDir = dirname(__DIR__, 2);
if (is_file($envDir . '/.env')) {
    Dotenv::createUnsafeImmutable($envDir)->safeLoad();
}

// Перекрываем env для тестов (phpunit.xml уже задаёт APP_ENV=test, но .env может перебить)
$_ENV['APP_ENV'] = 'test';
$_ENV['APP_DEBUG'] = '1';
