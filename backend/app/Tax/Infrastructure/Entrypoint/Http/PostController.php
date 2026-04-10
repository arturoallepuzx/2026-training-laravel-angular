<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\CreateTax\CreateTax;
use App\Tax\Infrastructure\Entrypoint\Http\Requests\TaxRequest;
use Illuminate\Http\JsonResponse;

class PostController
{
    public function __construct(
        private CreateTax $createTax,
    ) {}

    public function __invoke(TaxRequest $request, string $restaurantId): JsonResponse
    {
        $response = ($this->createTax)(
            $restaurantId,
            $request->validated('name'),
            $request->validated('percentage'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
