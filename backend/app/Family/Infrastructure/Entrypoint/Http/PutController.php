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
        $response = ($this->updateFamily)(
            $familyId,
            $restaurantId,
            $request->validated('name'),
            $request->validated('active'),
        );

        return new JsonResponse($response->toArray());
    }
}
