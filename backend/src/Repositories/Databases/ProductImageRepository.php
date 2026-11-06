<?php

declare(strict_types=1);

namespace App\Repositories\Databases;

use App\Entities\ProductImage;
use App\Repositories\ProductImageRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductImageRepository implements ProductImageRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function findById(int $id): ?ProductImage
    {
        return $this->em->find(ProductImage::class, $id);
    }

    public function save(ProductImage $image, bool $flush = true): void
    {
        $this->em->persist($image);

        if ($flush) {
            $this->em->flush();
        }
    }
}
