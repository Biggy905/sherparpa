<?php

declare(strict_types=1);

namespace App\Factories;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

final readonly class ApplicationFactory
{
    public function __construct(
        private string $rootDir,
    ) {
    }

    /**
     * @return App<ContainerInterface|null>
     */
    public function create(): App
    {
        AppFactory::setContainer($this->buildContainer());

        $app = AppFactory::create();

        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();

        // ErrorHandlerMiddleware подключается позже (см. п. Middleware)
        $app->addErrorMiddleware(
            displayErrorDetails: (bool) ($_ENV['APP_DEBUG'] ?? false),
            logErrors: true,
            logErrorDetails: true,
        );

        $routesFile = sprintf(
            '%s' . DIRECTORY_SEPARATOR . '%s',
            $this->rootDir,
            'backend/src/Config/routes.php',
        );

        (require $routesFile)($app);

        return $app;
    }

    private function buildContainer(): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAttributes(true);

        $builder->addDefinitions(
            require $this->rootDir . '/backend/src/Config/container.php',
        );

        return $builder->build();
    }
}
