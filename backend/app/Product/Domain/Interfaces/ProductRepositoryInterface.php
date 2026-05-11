<?php

declare(strict_types=1);

namespace App\Product\Domain\Interfaces;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\ValueObject\ProductName;
use App\Shared\Domain\ValueObject\Uuid;

interface ProductRepositoryInterface
{
    public function create(Product $product): void;

    public function update(Product $product): void;

    public function findById(Uuid $id, Uuid $restaurantId): ?Product;

    public function findByNameAndRestaurantId(ProductName $name, Uuid $restaurantId): ?Product;

    /** @return Product[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array;

    public function delete(Uuid $id, Uuid $restaurantId): void;
}
