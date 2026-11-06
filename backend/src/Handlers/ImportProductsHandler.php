<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Messages\ImportProductsMessage;
use App\Services\Import\ImportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportProductsHandler
{
    public function __construct(
        private ImportService $importService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ImportProductsMessage $message): void
    {
        $this->logger->info('Import started', [
            'job_id' => $message->jobId,
            'file' => $message->filePath,
        ]);

        try {
            $this->importService->import($message->filePath, $message->jobId);
        } catch (\Throwable $e) {
            $this->logger->error('Import failed', [
                'job_id' => $message->jobId,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}