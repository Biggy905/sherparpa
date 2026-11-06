<?php

declare(strict_types=1);

namespace App\DTO\WorkerMessage;

final readonly class ImportRowError
{
    public function __construct(
        public int $row,
        public string $message,
    ) {
    }

    /**
     * @return array{row: int, message: string}
     */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'message' => $this->message,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $row = $data['row'] ?? null;
        $message = $data['message'] ?? null;

        if (!\is_int($row) && !(\is_string($row) && \ctype_digit($row))) {
            throw new \InvalidArgumentException('ImportRowError: "row" must be an integer');
        }

        if (!\is_string($message)) {
            throw new \InvalidArgumentException('ImportRowError: "message" must be a string');
        }

        return new self((int) $row, $message);
    }
}
