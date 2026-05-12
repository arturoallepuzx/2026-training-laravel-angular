<?php

declare(strict_types=1);

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\ListZones\ListZones;
use Illuminate\Http\JsonResponse;

class GetAllController
{
    public function __construct(
        private ListZones $listZones,
    ) {}

    public function __invoke(string $restaurantId): JsonResponse
    {
        $response = ($this->listZones)($restaurantId);

        return new JsonResponse($response->toArray());
    }
}
