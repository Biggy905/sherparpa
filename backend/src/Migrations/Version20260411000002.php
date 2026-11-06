<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expression index on worker_jobs.message->image_id for image_download lookups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_worker_jobs_image_id
            ON worker_jobs (((message->>'image_id')::int))
            WHERE type = 'image_download'
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_worker_jobs_image_id');
    }
}
