<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\DTO\WorkerMessage\ImportMessage;
use App\Enums\WorkerJobStatus;
use App\Enums\WorkerJobType;
use App\Exceptions\ProductNotFoundException;
use App\Groups\ImportStatusGroup;
use App\Repositories\WorkerJobRepositoryInterface;

final readonly class ImportStatusService
{
    public function __construct(
        private WorkerJobRepositoryInterface $workerJobRepository,
    ) {
    }

    public function getStatus(string $jobId): ImportStatusGroup
    {
        $job = $this->workerJobRepository->findById($jobId);
        if ($job === null) {
            throw ProductNotFoundException::byId(0);
        }

        if ($job->getType() !== WorkerJobType::Import) {
            throw new \RuntimeException('Job is not an import job');
        }

        $payload = $job->getImportPayload();

        $products = [
            'status' => $job->getStatus()->value,
            'total' => $payload->totalRows,
            'processed' => $payload->processedRows,
            'failed' => $payload->failedRows,
            'progress_percent' => $payload->totalRows > 0
                ? (int) \round($payload->processedRows / $payload->totalRows * 100)
                : 0,
            'errors' => \array_map(
                static fn ($e) => ['row' => $e->row, 'message' => $e->message],
                $payload->errors,
            ),
        ];

        $images = $this->workerJobRepository->countImagesByStatusForJob($jobId);

        $stage = match (true) {
            $job->getStatus() === WorkerJobStatus::Failed => 'failed',
            $job->getStatus() !== WorkerJobStatus::Completed => 'products',
            $images['pending'] + $images['processing'] > 0 => 'images',
            default => 'done',
        };

        $status = match (true) {
            $job->getStatus() === WorkerJobStatus::Failed => 'failed',
            $stage === 'done' => 'completed',
            default => 'processing',
        };

        return new ImportStatusGroup(
            jobId: $jobId,
            status: $status,
            stage: $stage,
            products: $products,
            images: $images,
        );
    }
}
