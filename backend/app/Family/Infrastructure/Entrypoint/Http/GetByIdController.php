<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\GetFamilyById\GetFamilyById;
use Illuminate\Http\JsonResponse;

class GetByIdController
{
    public function __construct(
        private GetFamilyById $getFamilyById,
    ) {}

    public function __invoke(string $restaurantId, string $familyId): JsonResponse
    {
        $response = ($this->getFamilyById)($familyId, $restaurantId);

        return new JsonResponse($response->toArray());
    }
}
