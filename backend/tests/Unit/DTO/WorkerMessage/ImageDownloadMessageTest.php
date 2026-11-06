<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\WorkerMessage;

use App\DTO\WorkerMessage\ImageDownloadMessage;
use App\Enums\WorkerJobType;
use PHPUnit\Framework\TestCase;

final class ImageDownloadMessageTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $original = new ImageDownloadMessage(imageId: 42);
        $restored = ImageDownloadMessage::fromArray($original->toArray());

        self::assertSame(42, $restored->imageId);
        self::assertNull($restored->error);
    }

    public function testFromArrayRejectsMissingImageId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ImageDownloadMessage::fromArray([]);
    }

    public function testGetType(): void
    {
        $message = new ImageDownloadMessage(1);
        self::assertSame(WorkerJobType::ImageDownload, $message->getType());
    }
}
