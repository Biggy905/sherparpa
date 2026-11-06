<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Entities\Product;
use App\Entities\ProductAttribute;
use App\Entities\ProductImage;
use App\Enums\ImageType;
use Doctrine\Bundle\FixturesBundle\Fixture; // нет, мы standalone
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ProductFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $product1 = Product::create(
            'EXT-001',
            'Product One',
            'Description one',
            '100.00',
            '20.00',
        );

        $product1->updateFromImport(
            'Product One',
            'Description one',
            '100.00',
            '20.00',
            [
                new ProductAttribute($product1, 'Размер', 'M'),
                new ProductAttribute($product1, 'Цвет', 'Red'),
            ],
            [
                new ProductImage($product1, 'http://example.com/1.jpg', ImageType::Photo),
                new ProductImage($product1, 'http://example.com/1-pack.jpg', ImageType::Packaging),
            ],
        );

        $manager->persist($product1);

        $product2 = Product::create(
            'EXT-002',
            'Product Two',
            'Description two',
            '200.00',
            '10.00',
        );
        $manager->persist($product2);

        $manager->flush();
    }
}
