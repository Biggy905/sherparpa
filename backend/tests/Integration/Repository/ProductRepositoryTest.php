<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Entities\Product;
use App\Exceptions\ProductNotFoundException;
use App\Queries\ProductQuery;
use App\Repositories\Databases\ProductRepository;
use Tests\Integration\IntegrationTestCase;

final class ProductRepositoryTest extends IntegrationTestCase
{
    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository($this->em);
    }

    public function testSaveAndFindOne(): void
    {
        $product = Product::create('EXT-001', 'Test', null, '100.00', '10.00');
        $this->repository->save($product);

        $found = $this->repository->findOne(
            (new ProductQuery($this->em))->byExternalCode('EXT-001'),
        );

        self::assertSame('EXT-001', $found->getExternalCode());
        self::assertSame('100.00', $found->getPrice());
    }

    public function testFindOneThrowsWhenNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $this->repository->findOne(
            (new ProductQuery($this->em))->byExternalCode('MISSING'),
        );
    }

    public function testFindOneOrNullReturnsNull(): void
    {
        $result = $this->repository->findOneOrNull(
            (new ProductQuery($this->em))->byExternalCode('MISSING'),
        );

        self::assertNull($result);
    }

    public function testFindManyWithFiltersAndPagination(): void
    {
        foreach (['A', 'B', 'C'] as $i => $code) {
            $this->repository->save(Product::create(
                'EXT-' . $code,
                'Product ' . $code,
                null,
                (string) (($i + 1) * 100),
                '0.00',
            ));
        }

        $query = (new ProductQuery($this->em))
            ->byNameLike('Product')
            ->byPriceFrom(150.0)
            ->withPagination(10, 0);

        $result = $this->repository->findMany($query);
        self::assertCount(2, $result);

        $total = $this->repository->count($query);
        self::assertSame(2, $total);
    }

    public function testCountIgnoresPagination(): void
    {
        foreach (range(1, 5) as $i) {
            $this->repository->save(Product::create(
                'EXT-' . $i,
                'Product ' . $i,
                null,
                '100.00',
                '0.00',
            ));
        }

        $query = (new ProductQuery($this->em))->withPagination(2, 0);

        self::assertCount(2, $this->repository->findMany($query));
        self::assertSame(5, $this->repository->count($query));
    }

    public function testUpsertByExternalCode(): void
    {
        $this->repository->save(Product::create('EXT-001', 'Old', null, '100.00', '0.00'));

        $existing = $this->repository->findOneOrNull(
            (new ProductQuery($this->em))->byExternalCode('EXT-001'),
        );
        self::assertNotNull($existing);

        $existing->updateFromImport('New', null, '200.00', '0.00', [], []);
        $this->repository->save($existing);

        $reloaded = $this->repository->findOne(
            (new ProductQuery($this->em))->byExternalCode('EXT-001'),
        );

        self::assertSame('New', $reloaded->getName());
        self::assertSame('200.00', $reloaded->getPrice());
    }
}
