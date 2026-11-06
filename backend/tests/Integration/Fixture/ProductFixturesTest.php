<?php

declare(strict_types=1);

namespace Tests\Integration\Fixture;

use App\Entities\Product;
use Tests\Fixtures\ProductFixtures;
use Tests\Integration\IntegrationTestCase;

final class ProductFixturesTest extends IntegrationTestCase
{
    public function testLoad(): void
    {
        (new ProductFixtures())->load($this->em);

        $products = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->getQuery()
            ->getResult();

        self::assertCount(2, $products);
    }
}
