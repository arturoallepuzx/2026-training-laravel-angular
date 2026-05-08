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
        $response = ($this->updateRestaurant)(
            $restaurantId,
            $request->validated('name'),
            $request->validated('legal_name'),
            $request->validated('tax_id'),
            $request->validated('email'),
        );

        return new JsonResponse($response->toArray());
    }
}
