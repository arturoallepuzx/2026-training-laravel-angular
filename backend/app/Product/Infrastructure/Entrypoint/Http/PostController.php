<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\CreateProduct\CreateProduct;
use App\Product\Infrastructure\Entrypoint\Http\Requests\ProductRequest;
use Illuminate\Http\JsonResponse;

class PostController
{
    public function __construct(
        private CreateProduct $createProduct,
    ) {}

    public function __invoke(ProductRequest $request, string $restaurantId): JsonResponse
    {
        $response = ($this->createProduct)(
            $restaurantId,
            $request->validated('family_id'),
            $request->validated('tax_id'),
            $request->validated('image_src'),
            $request->validated('name'),
            $request->validated('price'),
            $request->validated('stock'),
            $request->validated('active') ?? true,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
