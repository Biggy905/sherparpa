<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class XlsxRow
{
    /**
     * @param array<string, string> $attributes
     * @param list<string>          $photoUrls
     */
    public function __construct(
        public int $rowNumber,
        public string $externalCode,
        public string $name,
        public ?string $description,
        public string $price,
        public string $purchasePrice,
        public string $discount,
        public array $attributes,
        public array $photoUrls,
        public ?string $packagingUrl,
    ) {
    }
}
