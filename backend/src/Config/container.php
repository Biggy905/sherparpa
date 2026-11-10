<?php

declare(strict_types=1);

use App\Factories\ConsumerBusFactory;
use App\Factories\EntityManagerFactory;
use App\Factories\MessageBusFactory;
use App\Factories\TransportFactory;
use App\Factories\ValidatorFactory;
use App\Repositories\Databases\ProductImageRepository;
use App\Repositories\Databases\ProductRepository;
use App\Repositories\Databases\WorkerJobRepository;
use App\Repositories\ProductImageRepositoryInterface;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\WorkerJobRepositoryInterface;
use App\Services\Health\HealthCheckService;
use App\Services\Import\ImageDownloader;
use App\Services\Import\ImportUploadService;
use App\Services\Product\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Factories\LoggerFactory;
use Psr\Log\LoggerInterface;

$rootDir = dirname(__DIR__, 3);
$appDir = dirname(__DIR__, 2);

$dsnRabbitMQ = 'amqp://guest:guest@localhost:5672/%2f';
if (!empty($_ENV['RABBITMQ_DSN']) && is_string($_ENV['RABBITMQ_DSN'])) {
    $dsnRabbitMQ = $_ENV['RABBITMQ_DSN'];
}

return [
    'rootDir' => $rootDir,
    'env' => static fn (): array => $_ENV,

    EntityManagerInterface::class => static function () use ($rootDir): EntityManagerInterface {
        return new EntityManagerFactory($rootDir)->create();
    },
    MessageBusInterface::class => static function () use ($dsnRabbitMQ): MessageBusInterface {
        return (new MessageBusFactory($dsnRabbitMQ))->create();
    },
    WorkerJobRepositoryInterface::class => \DI\autowire(WorkerJobRepository::class),
    ProductImageRepositoryInterface::class => \DI\autowire(ProductImageRepository::class),
    ProductRepositoryInterface::class => \DI\autowire(ProductRepository::class),
    TransportFactory::class => static function () use ($dsnRabbitMQ): TransportFactory {
        return new TransportFactory($dsnRabbitMQ);
    },

    LoggerInterface::class => static function () use ($appDir): LoggerInterface {
        $dir = $appDir . '/var/logs';

        return (new LoggerFactory($dir . '/logger.log'))->create();
    },

    ConsumerBusFactory::class => \DI\autowire(),

    Client::class => \DI\autowire(),

    ImageDownloader::class => static function (\Psr\Container\ContainerInterface $c) use ($appDir): ImageDownloader {
        $client = $c->get(Client::class);
        \assert($client instanceof Client);

        return new ImageDownloader($client, $appDir . '/var/storage');
    },
    ValidatorInterface::class => static fn (): ValidatorInterface => (new ValidatorFactory())->create(),

    HealthCheckService::class => static function (\Psr\Container\ContainerInterface $c): HealthCheckService {
        $em = $c->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $host = $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq';
        \assert(\is_string($host));

        $portRaw = $_ENV['RABBITMQ_PORT'] ?? 5672;
        $port = \is_numeric($portRaw) ? (int) $portRaw : 5672;

        return new HealthCheckService($em->getConnection(), $host, $port);
    },

    ImportUploadService::class => static function (\Psr\Container\ContainerInterface $c) use ($appDir): ImportUploadService {
        $repo = $c->get(WorkerJobRepositoryInterface::class);
        \assert($repo instanceof WorkerJobRepositoryInterface);

        $bus = $c->get(MessageBusInterface::class);
        \assert($bus instanceof MessageBusInterface);

        return new ImportUploadService($repo, $bus, $appDir . '/var/storage');
    },
];
