<?php

declare(strict_types=1);

namespace App\Factories;

use App\Handlers\DownloadProductImageHandler;
use App\Handlers\ImportProductsHandler;
use App\Messages\DownloadProductImageMessage;
use App\Messages\ImportProductsMessage;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final readonly class ConsumerBusFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function create(): MessageBusInterface
    {
        $handlersLocator = new HandlersLocator([
            ImportProductsMessage::class => [
                function (ImportProductsMessage $m): void {
                    $handler = $this->container->get(ImportProductsHandler::class);
                    \assert($handler instanceof ImportProductsHandler);
                    $handler($m);
                },
            ],
            DownloadProductImageMessage::class => [
                function (DownloadProductImageMessage $m): void {
                    $handler = $this->container->get(DownloadProductImageHandler::class);
                    \assert($handler instanceof DownloadProductImageHandler);
                    $handler($m);
                },
            ],
        ]);

        return new MessageBus([
            new HandleMessageMiddleware($handlersLocator),
        ]);
    }
}
