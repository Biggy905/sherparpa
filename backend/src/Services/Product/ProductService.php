<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Forms\FilterProductForm;
use App\Groups\PaginatedGroup;
use App\Groups\ProductItemGroup;
use App\Groups\ProductListGroup;
use App\Queries\ProductQuery;
use App\Repositories\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function getList(FilterProductForm $form): PaginatedGroup
    {
        $query = (new ProductQuery($this->em))
            ->byNameLike($form->name ?? '')
            ->byPriceFrom($form->priceFrom ?? 0.0)
            ->byPriceTo($form->priceTo ?? PHP_FLOAT_MAX)
            ->withPagination($form->limit, ($form->page - 1) * $form->limit);

        $products = $this->productRepository->findMany($query);
        $total = $this->productRepository->count($query);

        return new PaginatedGroup(
            items: new ProductListGroup($products),
            page: $form->page,
            limit: $form->limit,
            total: $total,
        );
    }

    public function getDetail(int $id): ProductItemGroup
    {
        $product = $this->productRepository->findOne(
            (new ProductQuery($this->em))->byId($id),
        );

        return new ProductItemGroup($product);
    }
}
