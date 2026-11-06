<?php

declare(strict_types=1);

namespace App\Groups;

use App\Components\GroupInterface;
use App\Entities\ProductImage;

final readonly class ProductImageGroup implements GroupInterface
{
    public function __construct(
        public ProductImage $image,
    ) {
    }

    public function toArray(): array
    {
        return [
            'url' => $this->image->getUrl(),
            'path' => $this->image->getPath(),
            'type' => $this->image->getType()->value,
        ];
    }
}
