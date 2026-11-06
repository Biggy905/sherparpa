<?php

declare(strict_types=1);

namespace App\Queries;

use App\Components\AbstractQuery;
use App\Entities\Product;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends AbstractQuery<Product>
 */
final class ProductQuery extends AbstractQuery
{
    private ?int $id = null;
    private ?string $externalCode = null;
    private ?string $name = null;
    private ?float $priceFrom = null;
    private ?float $priceTo = null;
    private ?int $limit = null;
    private ?int $offset = null;

    protected function entityClass(): string
    {
        return Product::class;
    }

    public function byId(int $id): self
    {
        $clone = clone $this;
        $clone->id = $id;

        return $clone;
    }

    public function byExternalCode(string $code): self
    {
        $clone = clone $this;
        $clone->externalCode = $code;

        return $clone;
    }

    public function byNameLike(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function byPriceFrom(float $price): self
    {
        $clone = clone $this;
        $clone->priceFrom = $price;

        return $clone;
    }

    public function byPriceTo(float $price): self
    {
        $clone = clone $this;
        $clone->priceTo = $price;

        return $clone;
    }

    public function withPagination(int $limit, int $offset): self
    {
        $clone = clone $this;
        $clone->limit = $limit;
        $clone->offset = $offset;

        return $clone;
    }

    public function getExternalCode(): ?string
    {
        return $this->externalCode;
    }

    public function build(): QueryBuilder
    {
        $qb = $this->qb('p');

        if ($this->id !== null) {
            $qb->andWhere('p.id = :id')
                ->setParameter('id', $this->id);
        }

        if ($this->externalCode !== null) {
            $qb->andWhere('p.externalCode = :externalCode')
                ->setParameter('externalCode', $this->externalCode);
        }

        if ($this->name !== null) {
            $qb->andWhere('p.name LIKE :name')
                ->setParameter('name', '%' . $this->name . '%');
        }

        if ($this->priceFrom !== null) {
            $qb->andWhere('p.price >= :priceFrom')
                ->setParameter('priceFrom', $this->priceFrom);
        }

        if ($this->priceTo !== null) {
            $qb->andWhere('p.price <= :priceTo')
                ->setParameter('priceTo', $this->priceTo);
        }

        if ($this->limit !== null) {
            $qb->setMaxResults($this->limit);
        }

        if ($this->offset !== null) {
            $qb->setFirstResult($this->offset);
        }

        return $qb;
    }
}
