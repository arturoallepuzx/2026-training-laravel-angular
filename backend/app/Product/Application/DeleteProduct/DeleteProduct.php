<?php

declare(strict_types=1);

namespace App\Product\Application\DeleteProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class DeleteProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): void
    {
        $idVO = Uuid::create($id);
        $restaurantIdVO = Uuid::create($restaurantId);

        $product = $this->productRepository->findById($idVO, $restaurantIdVO);

        if ($product === null) {
            throw ProductNotFoundException::forId($idVO);
        }

        $this->productRepository->delete($idVO, $restaurantIdVO);
    }
}
