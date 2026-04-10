<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\DeleteTax\DeleteTax;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteTax $deleteTax,
    ) {}

    public function __invoke(string $restaurantId, string $taxId): JsonResponse
    {
        ($this->deleteTax)($taxId, $restaurantId);

        return new JsonResponse(null, 204);
    }
}
