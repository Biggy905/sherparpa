<?php

declare(strict_types=1);

namespace App\Handlers;

use App\DTO\WorkerMessage\ImageDownloadMessage;
use App\Messages\DownloadProductImageMessage;
use App\Repositories\ProductImageRepositoryInterface;
use App\Repositories\WorkerJobRepositoryInterface;
use App\Services\Import\ImageDownloader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DownloadProductImageHandler
{
    public function __construct(
        private ProductImageRepositoryInterface $imageRepository,
        private WorkerJobRepositoryInterface    $workerJobRepository,
        private ImageDownloader                 $downloader,
        private LoggerInterface                 $logger,
    )
    {
    }

    public function __invoke(DownloadProductImageMessage $message): void
    {
        try {
            $image = $this->imageRepository->findById($message->imageId);
            if ($image === null) {
                $this->logger->warning('Image not found', ['image_id' => $message->imageId]);

                return;
            }

            $existingPath = $image->getPath();
            if ($existingPath !== null && \is_file($this->downloader->getStoragePath() . '/' . $existingPath)) {
                $this->logger->info('Image already downloaded', ['image_id' => $message->imageId]);

                return;
            }

            $job = $this->workerJobRepository->findImageDownloadJobByImageId($message->imageId);
            if ($job === null) {
                $this->logger->warning('WorkerJob for image not found', ['image_id' => $message->imageId]);

                return;
            }

            $job->markProcessing();
            $this->workerJobRepository->save($job);

            try {
                $relativePath = $this->downloader->download($image->getUrl(), $image->getId());
                $image->markDownloaded($relativePath);
                $this->imageRepository->save($image, false);

                $job->updatePayload(
                    $job->getImageDownloadPayload()->withError(null),
                );
                $job->markCompleted();
                $this->workerJobRepository->save($job);

                $this->logger->info('Image downloaded', [
                    'image_id' => $message->imageId,
                    'path' => $relativePath,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Image download failed', [
                    'image_id' => $message->imageId,
                    'exception' => $e,
                ]);

                $job->updatePayload(
                    $job->getImageDownloadPayload()->withError($e->getMessage()),
                );
                $job->markFailed();
                $this->workerJobRepository->save($job);

                throw $e;
            }
        } catch (\Throwable $e) {
            var_dump("ОШИБКА В ХЕНДЛЕРЕ: " . $e->getMessage());
            throw $e;
        }
    }
}
