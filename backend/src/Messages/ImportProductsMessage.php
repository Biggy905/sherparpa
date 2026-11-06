<?php

declare(strict_types=1);

namespace App\Messages;

final readonly class ImportProductsMessage
{
    public function __construct(
        public string $jobId,
        public string $filePath,
    ) {
    }
}
