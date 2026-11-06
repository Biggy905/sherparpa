<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidRowException extends RuntimeException
{
    public function __construct(
        public readonly int $rowNumber,
        string $message,
    ) {
        parent::__construct(\sprintf('Row %d: %s', $rowNumber, $message));
    }
}
