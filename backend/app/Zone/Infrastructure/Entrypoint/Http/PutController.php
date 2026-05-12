<?php

declare(strict_types=1);

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\UpdateZone\UpdateZone;
use App\Zone\Infrastructure\Entrypoint\Http\Requests\ZoneRequest;
use Illuminate\Http\JsonResponse;

class PutController
{
    public function __construct(
        private UpdateZone $updateZone,
    ) {}

    public function __invoke(ZoneRequest $request, string $restaurantId, string $zoneId): JsonResponse
    {
        $response = ($this->updateZone)(
            $zoneId,
            $restaurantId,
            $request->validated('name'),
        );

        return new JsonResponse($response->toArray());
    }
}
