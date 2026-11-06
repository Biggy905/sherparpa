<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Import\ImportStatusService;
use App\Services\Import\ImportUploadService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ImportController
{
    public function __construct(
        private ImportUploadService $uploadService,
        private ImportStatusService $statusService,
    ) {
    }

    #[OA\Post(
        path: '/api/imports',
        summary: 'Upload XLSX for async import',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 202, description: 'Accepted'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function create(Request $request, Response $response): Response
    {
        $data = $this->uploadService->handle($request);

        $response->getBody()->write((string) \json_encode($data, \JSON_THROW_ON_ERROR));
        $response = $response->withStatus(202);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param array<string, string> $args
     * @throws \JsonException
     */
    #[OA\Get(
        path: '/api/imports/{job_id}',
        summary: 'Get import status',
        parameters: [
            new OA\Parameter(name: 'job_id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Import status',
                content: new OA\JsonContent(ref: '#/components/schemas/ImportStatus'),
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function status(Request $request, Response $response, array $args): Response
    {
        $jobId = (string) ($args['job_id'] ?? '');
        $group = $this->statusService->getStatus($jobId);
        $response->getBody()->write((string) \json_encode($group->toArray(), \JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
