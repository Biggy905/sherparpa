<?php

declare(strict_types=1);

namespace App\Groups;

use App\Components\GroupInterface;
use App\Entities\Product;
use App\Entities\ProductAttribute;
use App\Entities\ProductImage;

final readonly class ProductItemGroup implements GroupInterface
{
    public function __construct(
        public Product $product,
    ) {
    }

    public function toArray(): array
    {
        $attributes = [];
        /** @var ProductAttribute $attribute */
        foreach ($this->product->getAttributes() as $attribute) {
            $attributes[$attribute->getKey()] = $attribute->getValue();
        }

        $images = [];
        /** @var ProductImage $image */
        foreach ($this->product->getImages() as $image) {
            $images[] = new ProductImageGroup($image)->toArray();
        }

        return [
            'id' => $this->product->getId(),
            'external_code' => $this->product->getExternalCode(),
            'name' => $this->product->getName(),
            'description' => $this->product->getDescription(),
            'price' => (float) $this->product->getPrice(),
            'discount' => (float) $this->product->getDiscount(),
            'attributes' => $attributes,
            'images' => $images,
        ];
    }
}
