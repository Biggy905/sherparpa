<?php

declare(strict_types=1);

namespace App\Groups;

use App\Components\GroupInterface;
use App\Entities\Product;

final readonly class ProductListGroup implements GroupInterface
{
    /**
     * @param list<Product> $products
     */
    public function __construct(
        public array $products,
    ) {
    }

    public function toArray(): array
    {
        $data = [];

        foreach ($this->products as $product) {
            $data[] = new ProductItemGroup($product)->toArray();
        }

        return $data;
    }
}
