<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\GetRestaurantById\GetRestaurantById;
use Illuminate\Http\JsonResponse;

class GetByIdController
{
    public function __construct(
        private GetRestaurantById $getRestaurantById,
    ) {}

    public function __invoke(string $restaurantId): JsonResponse
    {
        $response = ($this->getRestaurantById)($restaurantId);

        return new JsonResponse($response->toArray());
    }
}
