<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\UpdateFamily\UpdateFamily;
use App\Family\Infrastructure\Entrypoint\Http\Requests\UpdateFamilyRequest;
use Illuminate\Http\JsonResponse;

class PutController
{
    public function __construct(
        private UpdateFamily $updateFamily,
    ) {}

    public function __invoke(UpdateFamilyRequest $request, string $restaurantId, string $familyId): JsonResponse
    {
        $validated = $request->validated();

        $response = ($this->updateFamily)(
            $familyId,
            $restaurantId,
            $validated['name'] ?? null,
            $validated['active'] ?? null,
        );

        return new JsonResponse($response->toArray());
    }
}
