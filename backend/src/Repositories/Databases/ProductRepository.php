<?php

declare(strict_types=1);

namespace App\Repositories\Databases;

use App\Entities\Product;
use App\Exceptions\ProductNotFoundException;
use App\Queries\ProductQuery;
use App\Repositories\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;

final readonly class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function findOne(ProductQuery $query): Product
    {
        try {
            $result = $query->build()
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException) {
            throw ProductNotFoundException::byExternalCode(
                $query->getExternalCode() ?? '',
            );
        }

        \assert($result instanceof Product);

        return $result;
    }

    public function findOneOrNull(ProductQuery $query): ?Product
    {
        $result = $query->build()
            ->getQuery()
            ->getOneOrNullResult();

        \assert($result === null || $result instanceof Product);

        return $result;
    }

    public function findMany(ProductQuery $query): array
    {
        $result = $query->build()
            ->getQuery()
            ->getResult();

        \assert(\is_array($result));

        /** @var list<Product> $result */
        return $result;
    }

    public function count(ProductQuery $query): int
    {
        $result = $query->build()
            ->select('COUNT(p.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        \assert(\is_int($result) || \is_string($result));

        return (int) $result;
    }

    public function save(Product $product, bool $flush = true): void
    {
        $this->em->persist($product);

        if ($flush) {
            $this->em->flush();
        }
    }

    public function remove(Product $product, bool $flush = true): void
    {
        $this->em->remove($product);

        if ($flush) {
            $this->em->flush();
        }
    }
}
