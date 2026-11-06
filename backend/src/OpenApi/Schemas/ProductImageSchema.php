<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductImage',
    required: ['url', 'type'],
    properties: [
        new OA\Property(property: 'url', type: 'string', example: 'http://...'),
        new OA\Property(property: 'path', type: 'string', nullable: true, example: 'images/42.jpg'),
        new OA\Property(property: 'type', type: 'string', enum: ['photo', 'packaging']),
    ],
)]
final class ProductImageSchema
{
}
