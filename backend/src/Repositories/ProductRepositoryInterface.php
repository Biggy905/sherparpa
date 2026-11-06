<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Product;
use App\Exceptions\ProductNotFoundException;
use App\Queries\ProductQuery;

interface ProductRepositoryInterface
{
    /**
     * @throws ProductNotFoundException
     */
    public function findOne(ProductQuery $query): Product;

    public function findOneOrNull(ProductQuery $query): ?Product;

    /**
     * @return list<Product>
     */
    public function findMany(ProductQuery $query): array;

    public function count(ProductQuery $query): int;

    public function save(Product $product, bool $flush = true): void;

    public function remove(Product $product, bool $flush = true): void;
}
