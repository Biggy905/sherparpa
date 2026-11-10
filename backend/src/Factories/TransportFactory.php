<?php

declare(strict_types=1);

namespace App\Factories;

use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\TransportInterface;

final readonly class TransportFactory
{
    public function __construct(
        private string $rabbitMqDsn,
    ) {
    }

    public function create(string $queueName): TransportInterface
    {
        $factory = new AmqpTransportFactory();
        $serializer = new PhpSerializer();

        return $factory->createTransport(
            $this->rabbitMqDsn . '/' . $queueName,
            [
                'exchange' => [
                    'name' => 'messages',
                    'type' => 'direct',
                ],
                'queues' => [
                    $queueName => [
                        'binding_keys' => [$queueName],
                    ],
                ],
            ],
            $serializer,
        );
    }
}
