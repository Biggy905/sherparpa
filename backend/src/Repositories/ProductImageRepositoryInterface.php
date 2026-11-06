<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\ProductImage;

interface ProductImageRepositoryInterface
{
    public function findById(int $id): ?ProductImage;

    public function save(ProductImage $image, bool $flush = true): void;
}
