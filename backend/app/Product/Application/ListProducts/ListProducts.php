<?php

declare(strict_types=1);

namespace App\Product\Application\ListProducts;

use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ListProducts
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(string $restaurantId): ListProductsResponse
    {
        $products = $this->productRepository->findAllByRestaurantId(Uuid::create($restaurantId));

        return ListProductsResponse::create($products);
    }
}
