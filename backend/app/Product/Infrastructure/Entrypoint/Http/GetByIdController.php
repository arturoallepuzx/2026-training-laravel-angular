<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\GetProductById\GetProductById;
use Illuminate\Http\JsonResponse;

class GetByIdController
{
    public function __construct(
        private GetProductById $getProductById,
    ) {}

    public function __invoke(string $restaurantId, string $productId): JsonResponse
    {
        $response = ($this->getProductById)($productId, $restaurantId);

        return new JsonResponse($response->toArray());
    }
}
