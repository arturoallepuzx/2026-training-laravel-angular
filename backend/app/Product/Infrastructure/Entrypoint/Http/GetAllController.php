<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\ListProducts\ListProducts;
use Illuminate\Http\JsonResponse;

class GetAllController
{
    public function __construct(
        private ListProducts $listProducts,
    ) {}

    public function __invoke(string $restaurantId): JsonResponse
    {
        $response = ($this->listProducts)($restaurantId);

        return new JsonResponse($response->toArray());
    }
}
