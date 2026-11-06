<?php

declare(strict_types=1);

namespace App\Groups;

use App\Components\GroupInterface;

final readonly class PaginatedGroup implements GroupInterface
{
    public function __construct(
        public GroupInterface $items,
        public int $page,
        public int $limit,
        public int $total,
    ) {
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items->toArray(),
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'total_pages' => $this->limit > 0 ? (int) \ceil($this->total / $this->limit) : 0,
        ];
    }
}
