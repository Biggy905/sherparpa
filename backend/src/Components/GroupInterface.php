<?php

declare(strict_types=1);

namespace App\Components;

interface GroupInterface
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(): array;
}
