<?php

declare(strict_types=1);

use App\Factories\ApplicationFactory;
use Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$projectDir = dirname(__DIR__, 3);

$env = sprintf('%s/%s', $projectDir, '.env');
if (!is_file($env)) {
    throw new RuntimeException();
}

Dotenv::createUnsafeImmutable($projectDir)->load();

$app = new ApplicationFactory($projectDir)->create();

try {
    $app->run();
} catch (Throwable $e) {
    var_dump($e);
}
