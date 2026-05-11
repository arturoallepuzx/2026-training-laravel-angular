<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\UpdateProduct\UpdateProduct;
use App\Product\Infrastructure\Entrypoint\Http\Requests\UpdateProductRequest;
use Illuminate\Http\JsonResponse;

class PutController
{
    public function __construct(
        private UpdateProduct $updateProduct,
    ) {}

    public function __invoke(UpdateProductRequest $request, string $restaurantId, string $productId): JsonResponse
    {
        $validated = $request->validated();

        $response = ($this->updateProduct)(
            $productId,
            $restaurantId,
            $validated['family_id'] ?? null,
            $validated['tax_id'] ?? null,
            $validated['image_src'] ?? null,
            array_key_exists('image_src', $validated),
            $validated['name'] ?? null,
            $validated['price'] ?? null,
            $validated['stock'] ?? null,
            $validated['active'] ?? null,
        );

        return new JsonResponse($response->toArray());
    }
}
