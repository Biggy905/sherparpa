<?php

declare(strict_types=1);

namespace App\DTO\WorkerMessage;

use App\Enums\WorkerJobType;

final readonly class ImportMessage implements WorkerMessageInterface
{
    /**
     * @param list<ImportRowError> $errors
     */
    public function __construct(
        public string $file,
        public int $totalRows,
        public int $processedRows,
        public int $failedRows,
        public array $errors,
        public ?string $error = null,
    ) {
    }

    public function getType(): WorkerJobType
    {
        return WorkerJobType::Import;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'total_rows' => $this->totalRows,
            'processed_rows' => $this->processedRows,
            'failed_rows' => $this->failedRows,
            'errors' => \array_map(
                static fn (ImportRowError $e): array => $e->toArray(),
                $this->errors,
            ),
            'error' => $this->error,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $file = $data['file'] ?? null;
        if (!\is_string($file)) {
            throw new \InvalidArgumentException('ImportMessage: "file" must be a string');
        }

        $errorsRaw = $data['errors'] ?? [];
        if (!\is_array($errorsRaw)) {
            throw new \InvalidArgumentException('ImportMessage: "errors" must be an array');
        }

        /** @var list<ImportRowError> $errors */
        $errors = [];
        foreach ($errorsRaw as $item) {
            if (!\is_array($item)) {
                throw new \InvalidArgumentException('ImportMessage: each error must be an array');
            }
            /** @var array<string, mixed> $item */
            $errors[] = ImportRowError::fromArray($item);
        }

        $errorRaw = $data['error'] ?? null;
        if ($errorRaw !== null && !\is_string($errorRaw)) {
            throw new \InvalidArgumentException('ImportMessage: "error" must be a string or null');
        }

        return new static(
            file: $file,
            totalRows: self::intOrZero($data['total_rows'] ?? 0, 'total_rows'),
            processedRows: self::intOrZero($data['processed_rows'] ?? 0, 'processed_rows'),
            failedRows: self::intOrZero($data['failed_rows'] ?? 0, 'failed_rows'),
            errors: $errors,
            error: $errorRaw,
        );
    }

    public function withTotalRows(int $totalRows): self
    {
        return new self(
            $this->file,
            $totalRows,
            $this->processedRows,
            $this->failedRows,
            $this->errors,
            $this->error,
        );
    }

    public function withProcessedRows(int $processedRows): self
    {
        return new self(
            $this->file,
            $this->totalRows,
            $processedRows,
            $this->failedRows,
            $this->errors,
            $this->error,
        );
    }

    public function withFailedRows(int $failedRows): self
    {
        return new self(
            $this->file,
            $this->totalRows,
            $this->processedRows,
            $failedRows,
            $this->errors,
            $this->error,
        );
    }

    /**
     * @param list<ImportRowError> $errors
     */
    public function withErrors(array $errors): self
    {
        return new self(
            $this->file,
            $this->totalRows,
            $this->processedRows,
            $this->failedRows,
            $errors,
            $this->error,
        );
    }

    public function withError(?string $error): self
    {
        return new self(
            $this->file,
            $this->totalRows,
            $this->processedRows,
            $this->failedRows,
            $this->errors,
            $error,
        );
    }

    /**
     * @param mixed $value
     */
    private static function intOrZero(mixed $value, string $field): int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value) && \ctype_digit($value)) {
            return (int) $value;
        }

        throw new \InvalidArgumentException(\sprintf('ImportMessage: "%s" must be an integer', $field));
    }
}
