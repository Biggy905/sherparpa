<?php

declare(strict_types=1);

use App\Factories\EntityManagerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Dotenv\Dotenv;

$appDir = dirname(__DIR__);

require sprintf(
    '%s/%s',
    $appDir,
    'vendor/autoload.php'
);

$projectDir = dirname(__DIR__, 2);
$env = sprintf('%s/%s', $projectDir, '.env');
if (!is_file($env)) {
    throw new RuntimeException();
}

Dotenv::createUnsafeImmutable($projectDir)->load();

/** @var EntityManagerInterface $entityManager */
$entityManager = (new EntityManagerFactory($appDir))->create();

return $entityManager;
