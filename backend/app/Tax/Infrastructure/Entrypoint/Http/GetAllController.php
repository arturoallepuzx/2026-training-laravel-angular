<?php

declare(strict_types=1);

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\ListTaxes\ListTaxes;
use Illuminate\Http\JsonResponse;

class GetAllController
{
    public function __construct(
        private ListTaxes $listTaxes,
    ) {}

    public function __invoke(string $restaurantId): JsonResponse
    {
        $response = ($this->listTaxes)($restaurantId);

        return new JsonResponse($response->toArray());
    }
}
