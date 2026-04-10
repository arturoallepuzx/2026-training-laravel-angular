<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\GetTaxById\GetTaxById;
use Illuminate\Http\JsonResponse;

class GetByIdController
{
    public function __construct(
        private GetTaxById $getTaxById,
    ) {}

    public function __invoke(string $restaurantId, string $taxId): JsonResponse
    {
        $response = ($this->getTaxById)($taxId, $restaurantId);

        return new JsonResponse($response->toArray());
    }
}
