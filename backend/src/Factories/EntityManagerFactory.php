<?php

declare(strict_types=1);

namespace App\Factories;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use RuntimeException;

final readonly class EntityManagerFactory
{
    public function __construct(
        private string $rootDir,
    ) {
    }

    public function create(): EntityManagerInterface
    {
        $isDevMode = (bool) ($_ENV['APP_DEBUG'] ?? false);

        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [$this->rootDir . '/src/Entities'],
            isDevMode: $isDevMode,
            proxyDir: $this->rootDir . '/var/cache/doctrine/proxies',
        );

        $cache = $isDevMode
            ? new ArrayAdapter()
            : new FilesystemAdapter('doctrine', 0, $this->rootDir . '/var/cache/doctrine');

        $config->setMetadataCache($cache);
        $config->setQueryCache($cache);

        $config->setAutoGenerateProxyClasses($isDevMode);

        $connection = DriverManager::getConnection(
            $this->buildConnectionParams(),
            $config,
        );

        return new EntityManager($connection, $config);
    }

    /**
     * @return array{
     *     driver: 'pdo_pgsql',
     *     host: string,
     *     port: int,
     *     dbname: string,
     *     user: string,
     *     password: string,
     *     charset: string,
     * }
     */
    private function buildConnectionParams(): array
    {
        return [
            'driver' => $this->resolveDriver(),
            'host' => $this->envString('DB_HOST', '127.0.0.1'),
            'port' => $this->envInt('DB_PORT', 5432),
            'dbname' => $this->envString('DB_NAME', 'import_export'),
            'user' => $this->envString('DB_USER', 'app'),
            'password' => $this->envString('DB_PASSWORD', 'app'),
            'charset' => $this->envString('DB_CHARSET', 'utf8'),
        ];
    }

    private function envString(string $key, string $default): string
    {
        $value = $_ENV[$key] ?? $default;

        if (!is_string($value)) {
            throw new RuntimeException(sprintf('%s must be a string', $key));
        }

        return $value;
    }

    private function envInt(string $key, int $default): int
    {
        $value = $_ENV[$key] ?? $default;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw new RuntimeException(sprintf('%s must be an integer', $key));
    }

    /**
     * @return 'pdo_pgsql'
     */
    private function resolveDriver(): string
    {
        $driver = $this->envString('DB_DRIVER', 'pdo_pgsql');

        return match ($driver) {
            'pdo_pgsql' => 'pdo_pgsql',
            default => throw new RuntimeException(sprintf('Unsupported DB driver: %s', $driver)),
        };
    }
}
