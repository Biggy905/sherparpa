<?php

declare(strict_types=1);

namespace App\Factories;

use App\Messages\DownloadProductImageMessage;
use App\Messages\ImportProductsMessage;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final readonly class MessageBusFactory
{
    public function __construct(
        private string $rabbitMqDsn,
    ) {
    }

    public function create(): MessageBusInterface
    {
        $serializer = new PhpSerializer();
        $transportFactory = new AmqpTransportFactory();

        $importTransport = $transportFactory->createTransport($this->rabbitMqDsn . '/import', [], $serializer);
        $imagesTransport = $transportFactory->createTransport($this->rabbitMqDsn . '/images', [], $serializer);

        // Свой локатор: просто возвращает готовые транспорты, без контейнеров и алиасов
        $sendersLocator = new class ($importTransport, $imagesTransport) implements SendersLocatorInterface {
            public function __construct(
                private readonly \Symfony\Component\Messenger\Transport\Sender\SenderInterface $importTransport,
                private readonly \Symfony\Component\Messenger\Transport\Sender\SenderInterface $imagesTransport,
            ) {}

            public function getSenders(Envelope $envelope): iterable
            {
                $message = $envelope->getMessage();

                if ($message instanceof ImportProductsMessage) {
                    yield $this->importTransport;
                }

                if ($message instanceof DownloadProductImageMessage) {
                    yield $this->imagesTransport;
                }
            }
        };

        return new MessageBus([
            new SendMessageMiddleware($sendersLocator),
        ]);
    }
}
