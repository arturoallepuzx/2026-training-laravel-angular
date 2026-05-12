<?php

declare(strict_types=1);

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\CreateZone\CreateZone;
use App\Zone\Infrastructure\Entrypoint\Http\Requests\ZoneRequest;
use Illuminate\Http\JsonResponse;

class PostController
{
    public function __construct(
        private CreateZone $createZone,
    ) {}

    public function __invoke(ZoneRequest $request, string $restaurantId): JsonResponse
    {
        $response = ($this->createZone)(
            $restaurantId,
            $request->validated('name'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
