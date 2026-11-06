<?php

declare(strict_types=1);

namespace App\Factories;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

final readonly class ValidatorFactory
{
    public function create(): ValidatorInterface
    {
        return (new ValidatorBuilder())
            ->enableAttributeMapping()
            ->getValidator();
    }
}