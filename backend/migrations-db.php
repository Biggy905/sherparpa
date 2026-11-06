<?php

declare(strict_types=1);

use App\Factories\EntityManagerFactory;
use Dotenv\Dotenv;

$appDir = __DIR__;

require $appDir . '/vendor/autoload.php';

$projectDir = dirname($appDir);

$env = sprintf('%s/%s', $projectDir, '.env');
if (!is_file($env)) {
    throw new RuntimeException();
}

Dotenv::createUnsafeImmutable($projectDir)->load();

$em = (new EntityManagerFactory($appDir))->create();

return $em->getConnection();
