<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\WorkerJob;
use App\Enums\WorkerJobStatus;
use App\Enums\WorkerJobType;

interface WorkerJobRepositoryInterface
{
    public function findById(string $id): ?WorkerJob;

    public function findImageDownloadJobByImageId(int $imageId): ?WorkerJob;

    public function save(WorkerJob $job, bool $flush = true): void;

    /**
     * @return list<WorkerJob>
     */
    public function findByTypeAndStatus(WorkerJobType $type, WorkerJobStatus $status): array;

    /**
     * @return array{pending:int, processing:int, completed:int, failed:int, total:int}
     */
    public function countImagesByStatusForJob(string $jobId): array;

    /**
     * @return array<string, array<string, int>>
     */
    public function countByTypeAndStatus(): array;
}
