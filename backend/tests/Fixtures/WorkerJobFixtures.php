<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\DTO\WorkerMessage\ImageDownloadMessage;
use App\DTO\WorkerMessage\ImportMessage;
use App\Entities\WorkerJob;
use App\Enums\WorkerJobType;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class WorkerJobFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $importJob = new WorkerJob(
            WorkerJobType::Import,
            new ImportMessage(
                file: 'var/storage/uploads/test.xlsx',
                totalRows: 10,
                processedRows: 10,
                failedRows: 1,
                errors: [],
            ),
        );
        $importJob->markCompleted();
        $manager->persist($importJob);

        $imageJob = new WorkerJob(
            WorkerJobType::ImageDownload,
            new ImageDownloadMessage(imageId: 1, error: '404 Not Found'),
            $importJob->getId(),
        );
        $imageJob->markFailed();
        $manager->persist($imageJob);

        $manager->flush();
    }
}
