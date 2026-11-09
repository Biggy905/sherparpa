<?php

declare(strict_types=1);

namespace App\Groups;

use App\Components\GroupInterface;
use App\Entities\ProductImage;
use App\Entities\WorkerJob;

final readonly class ProductImageGroup implements GroupInterface
{
    public function __construct(
        public ProductImage $image,
        public ?WorkerJob $job = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'url' => $this->image->getUrl(),
            'path' => $this->image->getPath(),
            'type' => $this->image->getType()->value,
            'status' => $this->job?->getStatus()->value,
            'error' => $this->job?->getImageDownloadPayload()->error,
        ];
    }
}
