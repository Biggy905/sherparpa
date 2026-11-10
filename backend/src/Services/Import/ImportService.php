<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\DTO\WorkerMessage\ImportRowError;
use App\DTO\WorkerMessage\ImageDownloadMessage;
use App\DTO\XlsxRow;
use App\Entities\Product;
use App\Entities\ProductAttribute;
use App\Entities\ProductImage;
use App\Entities\WorkerJob;
use App\Enums\ImageType;
use App\Enums\WorkerJobType;
use App\Messages\DownloadProductImageMessage;
use App\Queries\ProductQuery;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\WorkerJobRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private XlsxParser $parser,
        private ProductRepositoryInterface $productRepository,
        private WorkerJobRepositoryInterface $workerJobRepository,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function import(string $filePath, string $jobId): void
    {
        $job = $this->workerJobRepository->findById($jobId);
        if ($job === null) {
            throw new \RuntimeException(\sprintf('WorkerJob "%s" not found', $jobId));
        }

        $job->markProcessing();
        $this->workerJobRepository->save($job);

        try {
            $parsed = $this->parser->parse($filePath);
        } catch (\Throwable $e) {
            $this->logger->error('Import parse failed', ['job_id' => $jobId, 'exception' => $e]);

            $job->updatePayload($job->getImportPayload()->withError($e->getMessage()));
            $job->markFailed();
            $this->workerJobRepository->save($job);

            throw $e;
        }

        /** @var list<ImportRowError> $errors */
        $errors = [];
        $processed = 0;
        $failed = 0;

        foreach ($parsed['errors'] as $rowError) {
            $errors[] = new ImportRowError($rowError->rowNumber, $rowError->getMessage());
            ++$failed;
            ++$processed;
        }

        $job->updatePayload(
            $job->getImportPayload()
                ->withTotalRows($parsed['total'])
                ->withProcessedRows($processed)
                ->withFailedRows($failed)
                ->withErrors($errors),
        );
        $this->workerJobRepository->save($job);

        foreach ($parsed['rows'] as $row) {
            try {
                // 1. Открываем транзакцию вручную
                $this->em->beginTransaction();

                // 2. Обрабатываем строку. processRow вернет массив ID картинок.
                $imageIds = $this->processRow($row, $jobId);

                // 3. Коммитим транзакцию (товар и картинки сохраняются в БД)
                $this->em->flush();
                $this->em->commit();

                // 4. Отправляем сообщения ТОЛЬКО ПОСЛЕ КОММИТА!
                foreach ($imageIds as $imageId) {
                    $this->logger->info('Dispatch image', ['id' => $imageId]); // Добавим лог для проверки
                    $this->bus->dispatch(new DownloadProductImageMessage($imageId));
                }
            } catch (\Throwable $e) {
                $this->logger->error('Ошибка в строке ' . $row->rowNumber . ': ' . $e->getMessage());
                $errors[] = new ImportRowError($row->rowNumber, $e->getMessage());
                ++$failed;
            } finally {
                ++$processed;
                $job->updatePayload(
                    $job->getImportPayload()
                        ->withProcessedRows($processed)
                        ->withFailedRows($failed)
                        ->withErrors($errors),
                );
                $this->workerJobRepository->save($job);
            }
        }

        $job->markCompleted();
        $this->workerJobRepository->save($job);
    }

    /**
     * @return list<int> ID картинок для скачивания
     */
    private function processRow(XlsxRow $row, string $jobId): array
    {
        $product = $this->productRepository->findOneOrNull(
            (new ProductQuery($this->em))->byExternalCode($row->externalCode),
        );

        if ($product === null) {
            $product = Product::create(
                $row->externalCode,
                $row->name,
                $row->description,
                $row->price,
                $row->discount,
            );
            $this->productRepository->save($product, false);
            $this->em->flush(); // Получаем ID товара для связей
        } else {
            // Если товар существует, нативно чистим старые атрибуты и картинки,
            // чтобы избежать конфликта Unique constraint при INSERT
            $this->em->getConnection()->executeStatement(
                'DELETE FROM product_attributes WHERE product_id = ?',
                [$product->getId()]
            );
            $this->em->getConnection()->executeStatement(
                'DELETE FROM product_images WHERE product_id = ?',
                [$product->getId()]
            );
            // Очищаем коллекции в сущности, чтобы Doctrine знала об этом
            $product->getAttributes()->clear();
            $product->getImages()->clear();
        }

        $attributes = [];
        foreach ($row->attributes as $key => $value) {
            $attributes[] = new ProductAttribute($product, $key, $value);
        }

        $images = [];
        foreach ($row->photoUrls as $url) {
            $images[] = new ProductImage($product, $url, ImageType::Photo);
        }
        if ($row->packagingUrl !== null) {
            $images[] = new ProductImage($product, $row->packagingUrl, ImageType::Packaging);
        }

        $product->updateFromImport(
            $row->name,
            $row->description,
            $row->price,
            $row->discount,
            $attributes,
            $images,
        );

        $this->productRepository->save($product, false);
        $this->em->flush();

        /** @var list<int> $imageIds */
        $imageIds = [];

        foreach ($product->getImages() as $image) {
            \assert($image->getId() > 0);
            $imageIds[] = $image->getId();

            $imageJob = new WorkerJob(
                WorkerJobType::ImageDownload,
                new ImageDownloadMessage($image->getId()),
                $jobId,
            );
            $this->workerJobRepository->save($imageJob, false);
        }

        $this->em->flush();

        return $imageIds;
    }
}
