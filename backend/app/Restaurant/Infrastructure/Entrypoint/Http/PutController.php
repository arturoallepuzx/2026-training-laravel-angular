<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\UpdateRestaurant\UpdateRestaurant;
use App\Restaurant\Infrastructure\Entrypoint\Http\Requests\UpdateRestaurantRequest;
use Illuminate\Http\JsonResponse;

class PutController
{
    public function __construct(
        private UpdateRestaurant $updateRestaurant,
    ) {}

    public function __invoke(UpdateRestaurantRequest $request, string $restaurantId): JsonResponse
    {
        $validated = $request->validated();

        $response = ($this->updateRestaurant)(
            $restaurantId,
            $validated['name'] ?? null,
            $validated['legal_name'] ?? null,
            $validated['tax_id'] ?? null,
            $validated['email'] ?? null,
        );

        return new JsonResponse($response->toArray());
    }
}
