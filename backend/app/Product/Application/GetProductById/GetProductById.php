<?php

declare(strict_types=1);

namespace App\Product\Application\GetProductById;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class GetProductById
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): GetProductByIdResponse
    {
        $productId = Uuid::create($id);

        $product = $this->productRepository->findById(
            $productId,
            Uuid::create($restaurantId),
        );

        if ($product === null) {
            throw ProductNotFoundException::forId($productId);
        }

        return GetProductByIdResponse::create($product);
    }
}
