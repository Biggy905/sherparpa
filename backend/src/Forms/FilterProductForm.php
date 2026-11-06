<?php

declare(strict_types=1);

namespace App\Forms;

use Symfony\Component\Validator\Constraints as Assert;

final class FilterProductForm
{
    #[Assert\Positive]
    public int $page = 1;

    #[Assert\Positive]
    #[Assert\LessThanOrEqual(100)]
    public int $limit = 20;

    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\PositiveOrZero]
    public ?float $priceFrom = null;

    #[Assert\PositiveOrZero]
    public ?float $priceTo = null;

    /**
     * @param array<string, mixed> $data
     */
    public function hydrate(array $data): void
    {
        if (isset($data['page']) && \is_numeric($data['page'])) {
            $this->page = (int) $data['page'];
        }

        if (isset($data['limit']) && \is_numeric($data['limit'])) {
            $this->limit = (int) $data['limit'];
        }

        if (isset($data['name']) && \is_string($data['name']) && $data['name'] !== '') {
            $this->name = $data['name'];
        }

        if (isset($data['price_from']) && \is_numeric($data['price_from'])) {
            $this->priceFrom = (float) $data['price_from'];
        }

        if (isset($data['price_to']) && \is_numeric($data['price_to'])) {
            $this->priceTo = (float) $data['price_to'];
        }
    }
}
