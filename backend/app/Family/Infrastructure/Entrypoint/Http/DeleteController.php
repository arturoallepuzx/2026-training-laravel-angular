<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\DeleteFamily\DeleteFamily;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteFamily $deleteFamily,
    ) {}

    public function __invoke(string $restaurantId, string $familyId): JsonResponse
    {
        ($this->deleteFamily)($familyId, $restaurantId);

        return new JsonResponse(null, 204);
    }
}
