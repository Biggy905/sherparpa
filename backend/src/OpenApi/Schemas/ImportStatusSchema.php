<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ImportStatus',
    required: ['job_id', 'status', 'stage', 'products', 'images'],
    properties: [
        new OA\Property(property: 'job_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'completed', 'failed']),
        new OA\Property(property: 'stage', type: 'string', enum: ['products', 'images', 'done', 'failed']),
        new OA\Property(property: 'products', type: 'object'),
        new OA\Property(property: 'images', type: 'object'),
    ],
)]
final class ImportStatusSchema
{
}
