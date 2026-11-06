<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationException extends RuntimeException
{
    public function __construct(
        public readonly ConstraintViolationListInterface $violations,
    ) {
        parent::__construct('Validation failed');
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    public function toArray(): array
    {
        $errors = [];

        foreach ($this->violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $errors;
    }
}
