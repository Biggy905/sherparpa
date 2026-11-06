<?php

declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Entities\Product;
use App\Repositories\Databases\ProductRepository;
use App\Repositories\Databases\WorkerJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Integration\IntegrationTestCase;

final class ImportTransactionTest extends IntegrationTestCase
{
    public function testRollbackOnlyFailedRow(): void
    {
        $productRepository = new ProductRepository($this->em);
        $workerJobRepository = new WorkerJobRepository($this->em);

        // Строка 1 — валидная, коммитится
        $this->em->wrapInTransaction(function () use ($productRepository): void {
            $product = Product::create('EXT-001', 'Valid', null, '100.00', '0.00');
            $productRepository->save($product, false);
            $this->em->flush();
        });

        // Строка 2 — невалидная, откатывается
        try {
            $this->em->wrapInTransaction(function () use ($productRepository): void {
                $product = Product::create('EXT-002', 'Invalid', null, '100.00', '0.00');
                $productRepository->save($product, false);
                $this->em->flush();
                throw new \RuntimeException('Row failed');
            });
        } catch (\RuntimeException) {
            // ожидаемо
        }

        // Строка 3 — валидная, коммитится
        $this->em->wrapInTransaction(function () use ($productRepository): void {
            $product = Product::create('EXT-003', 'Valid', null, '100.00', '0.00');
            $productRepository->save($product, false);
            $this->em->flush();
        });

        // Строка 1 и 3 на месте, строка 2 откатилась
        $query = $this->em->createQueryBuilder()
            ->select('p.externalCode')
            ->from(Product::class, 'p')
            ->getQuery()
            ->getArrayResult();

        $codes = array_column($query, 'externalCode');
        self::assertContains('EXT-001', $codes);
        self::assertContains('EXT-003', $codes);
        self::assertNotContains('EXT-002', $codes);
    }
}
