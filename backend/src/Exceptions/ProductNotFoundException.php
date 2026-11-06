<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ProductNotFoundException extends RuntimeException
{
    public static function byExternalCode(string $externalCode): self
    {
        return new self(\sprintf(
            'Product with external_code "%s" not found',
            $externalCode,
        ));
    }

    public static function byId(int $id): self
    {
        return new self(\sprintf('Product with id "%d" not found', $id));
    }
}
