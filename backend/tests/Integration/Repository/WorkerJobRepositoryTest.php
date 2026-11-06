<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\DTO\WorkerMessage\ImageDownloadMessage;
use App\DTO\WorkerMessage\ImportMessage;
use App\Entities\WorkerJob;
use App\Enums\WorkerJobStatus;
use App\Enums\WorkerJobType;
use App\Repositories\Databases\WorkerJobRepository;
use Tests\Integration\IntegrationTestCase;

final class WorkerJobRepositoryTest extends IntegrationTestCase
{
    private WorkerJobRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new WorkerJobRepository($this->em);
    }

    public function testSaveAndFindById(): void
    {
        $job = new WorkerJob(
            WorkerJobType::Import,
            new ImportMessage('test.xlsx', 10, 0, 0, []),
        );
        $this->repository->save($job);

        $found = $this->repository->findById($job->getId());
        self::assertNotNull($found);
        self::assertSame($job->getId(), $found->getId());
    }

    public function testFindImageDownloadJobByImageId(): void
    {
        $parent = new WorkerJob(WorkerJobType::Import, new ImportMessage('f', 1, 0, 0, []));
        $this->repository->save($parent);

        $imageJob = new WorkerJob(
            WorkerJobType::ImageDownload,
            new ImageDownloadMessage(imageId: 42),
            $parent->getId(),
        );
        $this->repository->save($imageJob);

        $found = $this->repository->findImageDownloadJobByImageId(42);

        self::assertNotNull($found);
        self::assertSame($imageJob->getId(), $found->getId());
    }

    public function testFindImageDownloadJobReturnsNullForMissing(): void
    {
        self::assertNull($this->repository->findImageDownloadJobByImageId(999));
    }

    public function testCountImagesByStatusForJob(): void
    {
        $parent = new WorkerJob(WorkerJobType::Import, new ImportMessage('f', 1, 0, 0, []));
        $this->repository->save($parent);

        foreach ([1, 2, 3] as $imageId) {
            $job = new WorkerJob(
                WorkerJobType::ImageDownload,
                new ImageDownloadMessage($imageId),
                $parent->getId(),
            );
            if ($imageId === 1) {
                $job->markCompleted();
            } elseif ($imageId === 2) {
                $job->markFailed();
            }
            $this->repository->save($job);
        }

        $counts = $this->repository->countImagesByStatusForJob($parent->getId());

        self::assertSame(1, $counts['completed']);
        self::assertSame(1, $counts['failed']);
        self::assertSame(1, $counts['pending']);
        self::assertSame(3, $counts['total']);
    }

    public function testCountByTypeAndStatus(): void
    {
        $import = new WorkerJob(WorkerJobType::Import, new ImportMessage('f', 1, 0, 0, []));
        $import->markCompleted();
        $this->repository->save($import);

        $image = new WorkerJob(WorkerJobType::ImageDownload, new ImageDownloadMessage(1));
        $image->markFailed();
        $this->repository->save($image);

        $counts = $this->repository->countByTypeAndStatus();

        self::assertSame(1, $counts[WorkerJobType::Import->value][WorkerJobStatus::Completed->value]);
        self::assertSame(1, $counts[WorkerJobType::ImageDownload->value][WorkerJobStatus::Failed->value]);
    }
}
