<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\DeleteProduct\DeleteProduct;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteProduct $deleteProduct,
    ) {}

    public function __invoke(string $restaurantId, string $productId): JsonResponse
    {
        ($this->deleteProduct)($productId, $restaurantId);

        return new JsonResponse(null, 204);
    }
}
