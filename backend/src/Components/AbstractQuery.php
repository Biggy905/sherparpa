<?php

declare(strict_types=1);

namespace App\Components;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * @template T of object
 */
abstract class AbstractQuery
{
    public function __construct(
        protected EntityManagerInterface $em,
    ) {
    }

    /**
     * @return class-string<T>
     */
    abstract protected function entityClass(): string;

    protected function qb(string $alias): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityClass(), $alias);
    }
}
