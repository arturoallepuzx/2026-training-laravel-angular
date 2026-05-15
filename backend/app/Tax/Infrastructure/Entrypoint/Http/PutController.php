<?php

declare(strict_types=1);

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\UpdateTax\UpdateTax;
use App\Tax\Infrastructure\Entrypoint\Http\Requests\UpdateTaxRequest;
use Illuminate\Http\JsonResponse;

class PutController
{
    public function __construct(
        private UpdateTax $updateTax,
    ) {}

    public function __invoke(UpdateTaxRequest $request, string $restaurantId, string $taxId): JsonResponse
    {
        $validated = $request->validated();

        $response = ($this->updateTax)(
            $taxId,
            $restaurantId,
            $validated['name'] ?? null,
            $validated['percentage'] ?? null,
        );

        return new JsonResponse($response->toArray());
    }
}
