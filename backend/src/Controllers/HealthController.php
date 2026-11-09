<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Health\HealthCheckService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class HealthController
{
    public function __construct(
        private HealthCheckService $healthCheckService,
    ) {
    }

    #[OA\Get(
        path: '/api/health',
        summary: 'Health check',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Health status',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'db', type: 'boolean'),
                        new OA\Property(property: 'rabbitmq', type: 'boolean'),
                    ],
                ),
            ),
        ],
    )]
    public function __invoke(Request $request, Response $response): Response
    {
        $data = $this->healthCheckService->check();

        $response->getBody()->write((string) \json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
