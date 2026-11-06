<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Entities\WorkerJob;
use App\Enums\WorkerJobType;
use App\DTO\WorkerMessage\ImportMessage;
use App\Exceptions\InvalidFileException;
use App\Messages\ImportProductsMessage;
use App\Repositories\WorkerJobRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ImportUploadService
{
    private const int MAX_SIZE = 20 * 1024 * 1024;

    public function __construct(
        private WorkerJobRepositoryInterface $workerJobRepository,
        private MessageBusInterface $bus,
        private string $storagePath,
    ) {
    }

    /**
     * @return array{job_id:string, status:string}
     */
    public function handle(ServerRequestInterface $request): array
    {
        $files = $request->getUploadedFiles();
        $file = $files['file'] ?? null;

        if ($file === null) {
            throw new InvalidFileException('File is missing');
        }

        if ($file->getSize() > self::MAX_SIZE) {
            throw new InvalidFileException('File is too large (max 20 MB)');
        }

        $filename = $file->getClientFilename() ?? '';
        if (!\str_ends_with(\strtolower($filename), '.xlsx')) {
            throw new InvalidFileException('Only .xlsx files are allowed');
        }

        $dir = $this->storagePath . '/uploads';
        if (!\is_dir($dir) && !\mkdir($dir, 0o775, true) && !\is_dir($dir)) {
            throw new \RuntimeException(\sprintf('Cannot create directory: %s', $dir));
        }

        $stored = $dir . '/' . \bin2hex(\random_bytes(16)) . '.xlsx';
        $file->moveTo($stored);

        $job = new WorkerJob(
            WorkerJobType::Import,
            new ImportMessage(
                file: $stored,
                totalRows: 0,
                processedRows: 0,
                failedRows: 0,
                errors: [],
            ),
        );
        $this->workerJobRepository->save($job);

        $this->bus->dispatch(new ImportProductsMessage($job->getId(), $stored));

        return ['job_id' => $job->getId(), 'status' => 'pending'];
    }
}
