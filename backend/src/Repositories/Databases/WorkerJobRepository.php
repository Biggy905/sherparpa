<?php

declare(strict_types=1);

namespace App\Repositories\Databases;

use App\DTO\WorkerMessage\ImageDownloadMessage;
use App\Entities\WorkerJob;
use App\Enums\WorkerJobStatus;
use App\Enums\WorkerJobType;
use App\Repositories\WorkerJobRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

final readonly class WorkerJobRepository implements WorkerJobRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function findById(string $id): ?WorkerJob
    {
        return $this->em->find(WorkerJob::class, $id);
    }

    public function findImageDownloadJobByImageId(int $imageId): ?WorkerJob
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(WorkerJob::class, 'w');
        $rsm->addFieldResult('w', 'id', 'id');
        $rsm->addFieldResult('w', 'job_id', 'jobId');
        $rsm->addFieldResult('w', 'type', 'type' );
        $rsm->addFieldResult('w', 'status', 'status');
        $rsm->addFieldResult('w', 'message', 'message');
        $rsm->addFieldResult('w', 'created_at', 'createdAt');
        $rsm->addFieldResult('w', 'updated_at', 'updatedAt');

        $sql = <<<'SQL'
            SELECT w.id, w.job_id, w.type, w.status, w.message, w.created_at, w.updated_at
            FROM worker_jobs w
            WHERE w.type = 'image_download'
              AND (w.message->>'image_id')::int = :imageId
            ORDER BY w.created_at DESC
            LIMIT 1
            SQL;

        $query = $this->em->createNativeQuery($sql, $rsm);
        $query->setParameter('imageId', $imageId);

        $result = $query->getOneOrNullResult();

        \assert($result === null || $result instanceof WorkerJob);

        return $result;
    }

    public function save(WorkerJob $job, bool $flush = true): void
    {
        $this->em->persist($job);

        if ($flush) {
            $this->em->flush();
        }
    }

    public function findByTypeAndStatus(WorkerJobType $type, WorkerJobStatus $status): array
    {
        $result = $this->em->createQueryBuilder()
            ->select('w')
            ->from(WorkerJob::class, 'w')
            ->where('w.type = :type')
            ->andWhere('w.status = :status')
            ->setParameter('type', $type)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();

        /** @var list<WorkerJob> $result */
        return $result;
    }

    public function countImagesByStatusForJob(string $jobId): array
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('w.status AS status', 'COUNT(w.id) AS cnt')
            ->from(WorkerJob::class, 'w')
            ->where('w.jobId = :jobId')
            ->andWhere('w.type = :type')
            ->setParameter('jobId', $jobId)
            ->setParameter('type', WorkerJobType::ImageDownload)
            ->groupBy('w.status')
            ->getQuery()
            ->getArrayResult();

        /** @var array<string, int> $byStatus */
        $byStatus = [];
        foreach ($rows as $row) {
            $status = $row['status'] ?? null;
            $cnt = $row['cnt'] ?? null;

            if (!$status instanceof WorkerJobStatus) {
                continue;
            }
            if (!\is_int($cnt) && !\is_string($cnt)) {
                continue;
            }

            $byStatus[$status->value] = (int) $cnt;
        }

        return [
            'pending' => $byStatus[WorkerJobStatus::Pending->value] ?? 0,
            'processing' => $byStatus[WorkerJobStatus::Processing->value] ?? 0,
            'completed' => $byStatus[WorkerJobStatus::Completed->value] ?? 0,
            'failed' => $byStatus[WorkerJobStatus::Failed->value] ?? 0,
            'total' => \array_sum($byStatus),
        ];
    }

    public function countByTypeAndStatus(): array
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('w.type AS type', 'w.status AS status', 'COUNT(w.id) AS cnt')
            ->from(WorkerJob::class, 'w')
            ->groupBy('w.type', 'w.status')
            ->getQuery()
            ->getArrayResult();

        /** @var array<string, array<string, int>> $result */
        $result = [];
        foreach ($rows as $row) {
            $type = $row['type'] ?? null;
            $status = $row['status'] ?? null;
            $cnt = $row['cnt'] ?? null;

            if (!$type instanceof WorkerJobType || !$status instanceof WorkerJobStatus) {
                continue;
            }
            if (!\is_int($cnt) && !\is_string($cnt)) {
                continue;
            }

            $result[$type->value][$status->value] = (int) $cnt;
        }

        return $result;
    }
}
