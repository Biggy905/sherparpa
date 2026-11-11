<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\Handlers\ImportProductsHandler;
use App\Messages\ImportProductsMessage;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;

final class ImportFlowTest extends E2ETestCase
{
    public function testFullImportFlow(): void
    {
        $fixture = dirname(__DIR__) . '/files/import_example_ok.xlsx';
        self::assertFileExists($fixture);

        // 1. POST /api/imports
        $response = $this->postFile('/api/imports', $fixture);
        self::assertSame(202, $response->getStatusCode());

        $body = $this->json($response);
        self::assertArrayHasKey('job_id', $body);
        self::assertSame('pending', $body['status']);

        $jobId = $body['job_id'];
        \assert(\is_string($jobId));

        // 2. Сообщение попало в InMemoryTransport
        $envelopes = $this->transport->getSent();
        self::assertCount(1, $envelopes);

        $envelope = $envelopes[0];
        $message = $envelope->getMessage();
        self::assertInstanceOf(ImportProductsMessage::class, $message);

        // 3. Вручную вызываем handler (эмуляция воркера)
        /** @var ContainerInterface|null $container */
        $container = $this->app->getContainer();
        /** @var ImportProductsHandler $handler */
        $handler = $container?->get(ImportProductsHandler::class);
        $handler($message);

        // 4. Проверяем статус импорта
        $statusResponse = $this->get('/api/imports/' . $jobId);
        self::assertSame(200, $statusResponse->getStatusCode());

        /** @var array{products: array{status: string, total: int, processed: int}} $status */
        $status = $this->json($statusResponse);
        self::assertSame('completed', $status['products']['status']);
        self::assertGreaterThan(0, $status['products']['total']);
        self::assertSame($status['products']['total'], $status['products']['processed']);

        // 5. Товары появились
        $productsResponse = $this->get('/api/products', ['page' => '1', 'limit' => '20']);
        self::assertSame(200, $productsResponse->getStatusCode());

        /** @var array{items: list<array{id: int}>, total: int} $products */
        $products = $this->json($productsResponse);
        self::assertArrayHasKey('items', $products);
        self::assertGreaterThan(0, $products['total']);

        // 6. Карточка первого товара
        $firstId = $products['items'][0]['id'];
        $detailResponse = $this->get('/api/products/' . $firstId);
        self::assertSame(200, $detailResponse->getStatusCode());

        /** @var array{external_code: string, attributes: array<string, mixed>, images: list<mixed>} $detail */
        $detail = $this->json($detailResponse);
        self::assertSame('AN-AZxf2i24obi-6ehzeU0', $detail['external_code']);
        self::assertArrayHasKey('attributes', $detail);
        self::assertArrayHasKey('images', $detail);
    }

    public function testImportRejectsMissingFile(): void
    {
        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())
            ->createServerRequest('POST', '/api/imports');

        $response = $this->app->handle($request);
        self::assertSame(422, $response->getStatusCode());
    }

    public function testProductsPagination(): void
    {
        $response = $this->get('/api/products', ['page' => '1', 'limit' => '5']);
        self::assertSame(200, $response->getStatusCode());
        $data = $this->json($response);
        self::assertSame(1, $data['page']);
        self::assertSame(5, $data['limit']);
    }

    public function testProductsValidationRejectsInvalidLimit(): void
    {
        $response = $this->get('/api/products', ['limit' => '99999']);
        self::assertSame(422, $response->getStatusCode());
    }

    public function testProductNotFound(): void
    {
        $response = $this->get('/api/products/99999');
        self::assertSame(404, $response->getStatusCode());
    }
}
