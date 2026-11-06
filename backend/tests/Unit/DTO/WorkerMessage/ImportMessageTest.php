<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\WorkerMessage;

use App\DTO\WorkerMessage\ImportMessage;
use App\DTO\WorkerMessage\ImportRowError;
use App\Enums\WorkerJobType;
use PHPUnit\Framework\TestCase;

final class ImportMessageTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $original = new ImportMessage(
            file: 'var/storage/uploads/test.xlsx',
            totalRows: 100,
            processedRows: 42,
            failedRows: 3,
            errors: [new ImportRowError(5, 'Missing external_code')],
            error: null,
        );

        $restored = ImportMessage::fromArray($original->toArray());

        self::assertSame($original->file, $restored->file);
        self::assertSame($original->totalRows, $restored->totalRows);
        self::assertSame($original->processedRows, $restored->processedRows);
        self::assertSame($original->failedRows, $restored->failedRows);
        self::assertCount(1, $restored->errors);
        self::assertSame(5, $restored->errors[0]->row);
        self::assertSame('Missing external_code', $restored->errors[0]->message);
        self::assertNull($restored->error);
    }

    public function testGetType(): void
    {
        $message = new ImportMessage('f', 0, 0, 0, []);
        self::assertSame(WorkerJobType::Import, $message->getType());
    }

    public function testFromArrayRejectsMissingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ImportMessage::fromArray([]);
    }

    public function testWithMethods(): void
    {
        $message = new ImportMessage('f', 0, 0, 0, []);

        $updated = $message
            ->withTotalRows(10)
            ->withProcessedRows(5)
            ->withFailedRows(1)
            ->withError('boom');

        self::assertSame(10, $updated->totalRows);
        self::assertSame(5, $updated->processedRows);
        self::assertSame(1, $updated->failedRows);
        self::assertSame('boom', $updated->error);

        // Иммутабельность: исходный не изменился
        self::assertSame(0, $message->totalRows);
        self::assertNull($message->error);
    }
}
