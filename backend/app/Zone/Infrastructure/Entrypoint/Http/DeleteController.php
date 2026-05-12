<?php

declare(strict_types=1);

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\DeleteZone\DeleteZone;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteZone $deleteZone,
    ) {}

    public function __invoke(string $restaurantId, string $zoneId): JsonResponse
    {
        ($this->deleteZone)($zoneId, $restaurantId);

        return new JsonResponse(null, 204);
    }
}
