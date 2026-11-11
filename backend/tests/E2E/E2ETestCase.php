<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\Factories\ApplicationFactory;
use App\Factories\EntityManagerFactory;
use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;

abstract class E2ETestCase extends TestCase
{
    /** @var App<ContainerInterface|null> */
    protected App $app;
    protected EntityManagerInterface $em;
    protected InMemoryTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = (new EntityManagerFactory(\dirname(__DIR__, 2)))->create();

        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $this->transport = new InMemoryTransport();

        $this->app = $this->buildApp();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();
    }

    /**
     * @return App<ContainerInterface|null>
     */
    private function buildApp(): App
    {
        $rootDir = \dirname(__DIR__, 2);

        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAttributes(true);
        $builder->addDefinitions(require $rootDir . '/src/Config/container.php');
        $builder->addDefinitions([
            EntityManagerInterface::class => fn (): EntityManagerInterface => $this->em,
            TransportInterface::class => fn (): InMemoryTransport => $this->transport,
            MessageBusInterface::class => fn (): MessageBusInterface => $this->buildBus(),
        ]);

        /** @var ContainerInterface $container */
        $container = $builder->build();

        \Slim\Factory\AppFactory::setContainer($container);
        $app = \Slim\Factory\AppFactory::create();
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->add(\App\Middlewares\ErrorHandlerMiddleware::class);
        $app->addErrorMiddleware(false, false, false);

        (require $rootDir . '/backend/src/Config/routes.php')($app);

        return $app;
    }

    private function buildBus(): MessageBusInterface
    {
        // Синхронный bus для тестов: dispatch → сразу в transport (in-memory)
        return new class ($this->transport) implements MessageBusInterface {
            public function __construct(
                private readonly InMemoryTransport $transport,
            ) {
            }

            public function dispatch(object $message, array $stamps = []): \Symfony\Component\Messenger\Envelope
            {
                $envelope = new \Symfony\Component\Messenger\Envelope($message);
                $this->transport->send($envelope);

                return $envelope;
            }
        };
    }

    /**
     * @param array<string, string> $query
     */
    protected function get(string $uri, array $query = []): \Psr\Http\Message\ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', $uri);
        if ($query !== []) {
            $request = $request->withQueryParams($query);
        }

        return $this->app->handle($request);
    }

    protected function postFile(string $uri, string $filePath, string $fieldName = 'file'): \Psr\Http\Message\ResponseInterface
    {
        // Создаем временную копию, чтобы не удалять оригинал
        $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        copy($filePath, $tempPath);

        $stream = (new StreamFactory())->createStreamFromFile($tempPath, 'r');
        $uploaded = (new UploadedFileFactory())->createUploadedFile(
            $stream,
            \filesize($tempPath) ?: 0,
            UPLOAD_ERR_OK,
            \basename($filePath),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', $uri)
            ->withUploadedFiles([$fieldName => $uploaded]);

        return $this->app->handle($request);
    }

    /**
     * @return array<mixed, mixed>
     */
    protected function json(\Psr\Http\Message\ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($decoded));

        return $decoded;
    }
}
