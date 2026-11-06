<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add job_id to worker_jobs for grouping image_download by import';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE worker_jobs ADD job_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_worker_jobs_job_type_status ON worker_jobs (job_id, type, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_worker_jobs_job_type_status');
        $this->addSql('ALTER TABLE worker_jobs DROP job_id');
    }
}
