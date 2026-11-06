<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create products, product_attributes, product_images, worker_jobs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE products (
                id SERIAL NOT NULL,
                external_code VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                price NUMERIC(10, 2) NOT NULL,
                discount NUMERIC(5, 2) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_products_external_code ON products (external_code)');

        $this->addSql(<<<'SQL'
            CREATE TABLE product_attributes (
                id SERIAL NOT NULL,
                product_id INT NOT NULL,
                attribute_key VARCHAR(255) NOT NULL,
                attribute_value TEXT NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE INDEX idx_product_attributes_product ON product_attributes (product_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_product_attributes_product_key ON product_attributes (product_id, attribute_key)');
        $this->addSql(<<<'SQL'
            ALTER TABLE product_attributes
                ADD CONSTRAINT fk_product_attributes_product
                FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE product_images (
                id SERIAL NOT NULL,
                product_id INT NOT NULL,
                url TEXT NOT NULL,
                path VARCHAR(512) DEFAULT NULL,
                type VARCHAR(20) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE INDEX idx_product_images_product ON product_images (product_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE product_images
                ADD CONSTRAINT fk_product_images_product
                FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE worker_jobs (
                id VARCHAR(36) NOT NULL,
                type VARCHAR(50) NOT NULL,
                status VARCHAR(20) NOT NULL,
                message JSONB NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE INDEX idx_worker_jobs_type_status ON worker_jobs (type, status)');
        $this->addSql('CREATE INDEX idx_worker_jobs_status_created ON worker_jobs (status, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE worker_jobs');
        $this->addSql('ALTER TABLE product_images DROP CONSTRAINT fk_product_images_product');
        $this->addSql('DROP TABLE product_images');
        $this->addSql('ALTER TABLE product_attributes DROP CONSTRAINT fk_product_attributes_product');
        $this->addSql('DROP TABLE product_attributes');
        $this->addSql('DROP TABLE products');
    }
}
