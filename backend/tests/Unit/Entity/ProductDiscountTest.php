<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Entities\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductDiscountTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function discountProvider(): iterable
    {
        yield 'zero price' => ['0.00', '0.00', '0.00'];
        yield 'zero purchase' => ['100.00', '0.00', '100.00'];
        yield 'typical 20%' => ['100.00', '80.00', '20.00'];
        yield 'typical 33.33%' => ['780.00', '880.00', '-12.82'];
        yield 'purchase > price' => ['100.00', '120.00', '-20.00'];
        yield 'rounding' => ['99.99', '66.66', '33.33'];
    }

    #[DataProvider('discountProvider')]
    public function testCalculateDiscount(string $price, string $purchasePrice, string $expected): void
    {
        self::assertSame($expected, Product::calculateDiscount($price, $purchasePrice));
    }
}
