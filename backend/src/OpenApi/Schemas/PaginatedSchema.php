<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Paginated',
    required: ['items', 'page', 'limit', 'total', 'total_pages'],
    properties: [
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'limit', type: 'integer', example: 20),
        new OA\Property(property: 'total', type: 'integer', example: 100),
        new OA\Property(property: 'total_pages', type: 'integer', example: 5),
    ],
)]
final class PaginatedSchema
{
}
