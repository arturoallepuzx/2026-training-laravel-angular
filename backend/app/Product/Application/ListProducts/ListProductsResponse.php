<?php

declare(strict_types=1);

namespace App\Product\Application\ListProducts;

use App\Product\Domain\Entity\Product;

final readonly class ListProductsResponse
{
    /** @param array<int, array<string, mixed>> $products */
    public function __construct(
        public array $products,
    ) {}

    /** @param Product[] $products */
    public static function create(array $products): self
    {
        return new self(
            products: array_map(fn (Product $product) => [
                'id' => $product->id()->value(),
                'restaurant_id' => $product->restaurantId()->value(),
                'family_id' => $product->familyId()->value(),
                'tax_id' => $product->taxId()->value(),
                'image_src' => $product->imageSrc()?->value(),
                'name' => $product->name()->value(),
                'price' => $product->price()->value(),
                'stock' => $product->stock()->value(),
                'active' => $product->active(),
                'created_at' => $product->createdAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $product->updatedAt()->format(\DateTimeInterface::ATOM),
            ], $products),
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return $this->products;
    }
}
