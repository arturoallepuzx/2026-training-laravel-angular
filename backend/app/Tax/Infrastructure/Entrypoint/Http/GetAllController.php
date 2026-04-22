<?php

declare(strict_types=1);

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\GetAllTaxes\GetAllTaxes;
use Illuminate\Http\JsonResponse;

class GetAllController
{
    public function __construct(
        private GetAllTaxes $getAllTaxes,
    ) {}

    public function __invoke(string $restaurantId): JsonResponse
    {
        $response = ($this->getAllTaxes)($restaurantId);

        return new JsonResponse($response->toArray());
    }
}
