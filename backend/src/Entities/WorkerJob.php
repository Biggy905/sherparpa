<?php

declare(strict_types=1);

namespace App\Entities;

use App\DTO\WorkerMessage\ImageDownloadMessage;
use App\DTO\WorkerMessage\ImportMessage;
use App\DTO\WorkerMessage\WorkerMessageInterface;
use App\Enums\WorkerJobStatus;
use App\Enums\WorkerJobType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'worker_jobs')]
#[ORM\Index(name: 'idx_worker_jobs_type_status', columns: ['type', 'status'])]
#[ORM\Index(name: 'idx_worker_jobs_status_created', columns: ['status', 'created_at'])]
#[ORM\Index(name: 'idx_worker_jobs_job_type_status', columns: ['job_id', 'type', 'status'])]
class WorkerJob
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(name: 'job_id', type: Types::STRING, length: 36, nullable: true)]
    private ?string $jobId = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: WorkerJobType::class)]
    private WorkerJobType $type;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: WorkerJobStatus::class)]
    private WorkerJobStatus $status = WorkerJobStatus::Pending;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $message;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        WorkerJobType $type,
        WorkerMessageInterface $payload,
        ?string $jobId = null,
    ) {
        if ($payload->getType() !== $type) {
            throw new \InvalidArgumentException(\sprintf(
                'Payload type "%s" does not match job type "%s"',
                $payload->getType()->value,
                $type->value,
            ));
        }

        $this->id = Uuid::v4()->toRfc4122();
        $this->type = $type;
        $this->message = $payload->toArray();
        $this->jobId = $jobId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }

    public function getType(): WorkerJobType
    {
        return $this->type;
    }

    public function getStatus(): WorkerJobStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPayload(): WorkerMessageInterface
    {
        return match ($this->type) {
            WorkerJobType::Import => ImportMessage::fromArray($this->message),
            WorkerJobType::ImageDownload => ImageDownloadMessage::fromArray($this->message),
        };
    }

    public function getImportPayload(): ImportMessage
    {
        $payload = $this->getPayload();
        \assert($payload instanceof ImportMessage);

        return $payload;
    }

    public function getImageDownloadPayload(): ImageDownloadMessage
    {
        $payload = $this->getPayload();
        \assert($payload instanceof ImageDownloadMessage);

        return $payload;
    }

    public function updatePayload(WorkerMessageInterface $payload): void
    {
        if ($payload->getType() !== $this->type) {
            throw new \InvalidArgumentException(\sprintf(
                'Payload type "%s" does not match job type "%s"',
                $payload->getType()->value,
                $this->type->value,
            ));
        }

        $this->message = $payload->toArray();
        $this->touch();
    }

    public function markProcessing(): void
    {
        $this->status = WorkerJobStatus::Processing;
        $this->touch();
    }

    public function markCompleted(): void
    {
        $this->status = WorkerJobStatus::Completed;
        $this->touch();
    }

    public function markFailed(): void
    {
        $this->status = WorkerJobStatus::Failed;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
