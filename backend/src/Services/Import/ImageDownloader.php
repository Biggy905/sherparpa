<?php

declare(strict_types=1);

namespace App\Services\Import;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

final readonly class ImageDownloader
{
    public function __construct(
        private Client $httpClient,
        private string $storagePath,
    ) {
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * @throws RuntimeException
     */
    public function download(string $url, int $imageId): string
    {
        $dir = $this->storagePath . '/images';
        if (!\is_dir($dir) && !\mkdir($dir, 0o775, true) && !\is_dir($dir)) {
            throw new RuntimeException(\sprintf('Cannot create directory: %s', $dir));
        }

        $extension = $this->resolveExtension($url);
        $relativePath = \sprintf('images/%d.%s', $imageId, $extension);
        $absolutePath = $this->storagePath . '/' . $relativePath;

        $attempt = 0;
        $lastError = null;

        while ($attempt < 2) {
            ++$attempt;

            try {
                $response = $this->httpClient->request('GET', $url, [
                    'timeout' => 10,
                    'connect_timeout' => 5,
                    'http_errors' => true,
                ]);

                $body = (string) $response->getBody();
                if ($body === '') {
                    throw new RuntimeException('Empty response body');
                }

                if (\file_put_contents($absolutePath, $body) === false) {
                    throw new RuntimeException(\sprintf('Cannot write file: %s', $absolutePath));
                }

                return $relativePath;
            } catch (GuzzleException | RuntimeException $e) {
                $lastError = $e;
            }
        }

        throw new RuntimeException(
            \sprintf(
                'Failed to download image after %d attempts: %s',
                $attempt,
                $lastError->getMessage(),
            ),
            previous: $lastError,
        );
    }

    private function resolveExtension(string $url): string
    {
        $path = \parse_url($url, \PHP_URL_PATH);
        if (\is_string($path)) {
            $ext = \strtolower(\pathinfo($path, \PATHINFO_EXTENSION));
            if (\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                return $ext;
            }
        }

        return 'jpg';
    }
}
