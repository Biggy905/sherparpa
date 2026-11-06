<?php

declare(strict_types=1);

namespace App\DTO\WorkerMessage;

use App\Enums\WorkerJobType;

interface WorkerMessageInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static;

    public function getType(): WorkerJobType;
}
