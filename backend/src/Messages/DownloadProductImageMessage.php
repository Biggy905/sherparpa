<?php

declare(strict_types=1);

namespace App\Messages;

final readonly class DownloadProductImageMessage
{
    public function __construct(
        public int $imageId,
    ) {
    }
}
