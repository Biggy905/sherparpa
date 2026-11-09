<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Exceptions\InvalidFileException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ValidationException $e) {
            return $this->json(422, ['errors' => $e->toArray()]);
        } catch (InvalidFileException $e) {
            return $this->json(422, ['error' => $e->getMessage()]);
        } catch (ProductNotFoundException $e) {
            return $this->json(404, ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->json(500, ['error' => 'Internal Server Error']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function json(int $status, array $data): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write((string) \json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
