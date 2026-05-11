<?php

declare(strict_types=1);

namespace App\Product\Application\UpdateProduct;

use App\Product\Domain\Entity\Product;

final readonly class UpdateProductResponse
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public string $familyId,
        public string $taxId,
        public ?string $imageSrc,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Product $product): self
    {
        return new self(
            id: $product->id()->value(),
            restaurantId: $product->restaurantId()->value(),
            familyId: $product->familyId()->value(),
            taxId: $product->taxId()->value(),
            imageSrc: $product->imageSrc()?->value(),
            name: $product->name()->value(),
            price: $product->price()->value(),
            stock: $product->stock()->value(),
            active: $product->active(),
            createdAt: $product->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $product->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurantId,
            'family_id' => $this->familyId,
            'tax_id' => $this->taxId,
            'image_src' => $this->imageSrc,
            'name' => $this->name,
            'price' => $this->price,
            'stock' => $this->stock,
            'active' => $this->active,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
