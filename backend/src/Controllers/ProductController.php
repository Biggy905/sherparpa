<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Forms\FilterProductForm;
use App\Services\Product\ProductService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ProductController
{
    public function __construct(
        private ProductService $productService,
        private ValidatorInterface $validator,
    ) {
    }

    #[OA\Get(
        path: '/api/products',
        summary: 'List products with pagination and filters',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'name', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'price_from', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'price_to', in: 'query', schema: new OA\Schema(type: 'number')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list',
                content: new OA\JsonContent(ref: '#/components/schemas/Paginated'),
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function list(Request $request, Response $response): Response
    {
        $form = new FilterProductForm();
        $form->hydrate($request->getQueryParams());

        $violations = $this->validator->validate($form);
        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $group = $this->productService->getList($form);
        $response->getBody()->write((string) \json_encode($group->toArray(), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param array<string, string> $args
     */
    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Get product detail',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product detail',
                content: new OA\JsonContent(ref: '#/components/schemas/Product'),
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function detail(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $group = $this->productService->getDetail($id);
        $response->getBody()->write((string) \json_encode($group->toArray(), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
