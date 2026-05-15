<?php

declare(strict_types=1);

namespace App\Product\Domain\Entity;

use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Product
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private Uuid $restaurantId,
        private Uuid $familyId,
        private Uuid $taxId,
        private ?ProductImageSrc $imageSrc,
        private ProductName $name,
        private ProductPrice $price,
        private ProductStock $stock,
        private bool $active,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $restaurantId,
        Uuid $familyId,
        Uuid $taxId,
        ?ProductImageSrc $imageSrc,
        ProductName $name,
        ProductPrice $price,
        ProductStock $stock,
        bool $active = true,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $restaurantId,
            $familyId,
            $taxId,
            $imageSrc,
            $name,
            $price,
            $stock,
            $active,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $familyId,
        string $taxId,
        ?string $imageSrc,
        string $name,
        int $price,
        int $stock,
        bool $active,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($restaurantId),
            Uuid::create($familyId),
            Uuid::create($taxId),
            $imageSrc !== null ? ProductImageSrc::create($imageSrc) : null,
            ProductName::create($name),
            ProductPrice::create($price),
            ProductStock::create($stock),
            $active,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function updateFamilyId(Uuid $familyId): void
    {
        if ($this->familyId->value() === $familyId->value()) {
            return;
        }

        $this->familyId = $familyId;
        $this->touch();
    }

    public function updateTaxId(Uuid $taxId): void
    {
        if ($this->taxId->value() === $taxId->value()) {
            return;
        }

        $this->taxId = $taxId;
        $this->touch();
    }

    public function updateImageSrc(?ProductImageSrc $imageSrc): void
    {
        if ($this->imageSrc?->value() === $imageSrc?->value()) {
            return;
        }

        $this->imageSrc = $imageSrc;
        $this->touch();
    }

    public function updateName(ProductName $name): void
    {
        if ($this->name->value() === $name->value()) {
            return;
        }

        $this->name = $name;
        $this->touch();
    }

    public function updatePrice(ProductPrice $price): void
    {
        if ($this->price->value() === $price->value()) {
            return;
        }

        $this->price = $price;
        $this->touch();
    }

    public function updateStock(ProductStock $stock): void
    {
        if ($this->stock->value() === $stock->value()) {
            return;
        }

        $this->stock = $stock;
        $this->touch();
    }

    public function updateActive(bool $active): void
    {
        if ($this->active === $active) {
            return;
        }

        $this->active = $active;
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function familyId(): Uuid
    {
        return $this->familyId;
    }

    public function taxId(): Uuid
    {
        return $this->taxId;
    }

    public function imageSrc(): ?ProductImageSrc
    {
        return $this->imageSrc;
    }

    public function name(): ProductName
    {
        return $this->name;
    }

    public function price(): ProductPrice
    {
        return $this->price;
    }

    public function stock(): ProductStock
    {
        return $this->stock;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
