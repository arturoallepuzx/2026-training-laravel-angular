<?php

declare(strict_types=1);

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\GetZoneById\GetZoneById;
use Illuminate\Http\JsonResponse;

class GetByIdController
{
    public function __construct(
        private GetZoneById $getZoneById,
    ) {}

    public function __invoke(string $restaurantId, string $zoneId): JsonResponse
    {
        $response = ($this->getZoneById)($zoneId, $restaurantId);

        return new JsonResponse($response->toArray());
    }
}
