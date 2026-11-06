<?php

declare(strict_types=1);

namespace App\DTO\WorkerMessage;

use App\Enums\WorkerJobType;

final readonly class ImageDownloadMessage implements WorkerMessageInterface
{
    public function __construct(
        public int $imageId,
        public ?string $error = null,
    ) {
    }

    public function getType(): WorkerJobType
    {
        return WorkerJobType::ImageDownload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'image_id' => $this->imageId,
            'error' => $this->error,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $imageId = $data['image_id'] ?? null;

        if (!\is_int($imageId) && !(\is_string($imageId) && \ctype_digit($imageId))) {
            throw new \InvalidArgumentException('ImageDownloadMessage: "image_id" must be an integer');
        }

        $errorRaw = $data['error'] ?? null;
        if ($errorRaw !== null && !\is_string($errorRaw)) {
            throw new \InvalidArgumentException('ImageDownloadMessage: "error" must be a string or null');
        }

        return new static((int) $imageId, $errorRaw);
    }

    public function withError(?string $error): self
    {
        return new self($this->imageId, $error);
    }
}
