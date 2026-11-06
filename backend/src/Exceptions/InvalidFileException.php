<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidFileException extends RuntimeException
{
    public static function notReadable(string $path): self
    {
        return new self(\sprintf('File is not readable: %s', $path));
    }

    public static function missingHeaders(): self
    {
        return new self('XLSX file has no header row');
    }
}
