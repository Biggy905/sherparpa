<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Product',
    required: ['id', 'external_code', 'name', 'price', 'discount'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'external_code', type: 'string', example: '3UHfAid1jaMiwgBuNvnsf3'),
        new OA\Property(property: 'name', type: 'string', example: 'Бермуды мужские'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 1320.00),
        new OA\Property(property: 'discount', type: 'number', format: 'float', example: 33.33),
        new OA\Property(property: 'attributes', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string')),
        new OA\Property(
            property: 'images',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProductImage'),
        ),
    ],
)]
final class ProductSchema
{
}
