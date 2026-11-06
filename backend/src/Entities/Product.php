<?php

declare(strict_types=1);

namespace App\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\UniqueConstraint(name: 'uniq_products_external_code', columns: ['external_code'])]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(name: 'external_code', type: Types::STRING, length: 255, unique: true)]
    private string $externalCode;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $discount = '0.00';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ProductAttribute> */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductAttribute::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $attributes;

    /** @var Collection<int, ProductImage> */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductImage::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $images;

    public function __construct()
    {
        $this->attributes = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        string $externalCode,
        string $name,
        ?string $description,
        string $price,
        string $discount,
    ): self {
        $product = new self();
        $product->externalCode = $externalCode;
        $product->name = $name;
        $product->description = $description;
        $product->price = $price;
        $product->discount = $discount;

        return $product;
    }

    /**
     * @param list<ProductAttribute> $attributes
     * @param list<ProductImage>     $images
     */
    public function updateFromImport(
        string $name,
        ?string $description,
        string $price,
        string $discount,
        array $attributes,
        array $images,
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->discount = $discount;

        $this->attributes->clear();
        foreach ($attributes as $attribute) {
            $attribute->setProduct($this);
            $this->attributes->add($attribute);
        }

        $this->images->clear();
        foreach ($images as $image) {
            $image->setProduct($this);
            $this->images->add($image);
        }

        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getExternalCode(): string
    {
        return $this->externalCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getDiscount(): string
    {
        return $this->discount;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, ProductAttribute> */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    /** @return Collection<int, ProductImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public static function calculateDiscount(string $price, string $purchasePrice): string
    {
        $priceFloat = (float) $price;

        if ($priceFloat <= 0.0) {
            return '0.00';
        }

        $discount = ((float) $price - (float) $purchasePrice) / $priceFloat * 100.0;

        return \number_format($discount, 2, '.', '');
    }
}
