<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\CreateFamily\CreateFamily;
use App\Family\Infrastructure\Entrypoint\Http\Requests\FamilyRequest;
use Illuminate\Http\JsonResponse;

class PostController
{
    public function __construct(
        private CreateFamily $createFamily,
    ) {}

    public function __invoke(FamilyRequest $request, string $restaurantId): JsonResponse
    {
        $response = ($this->createFamily)(
            $restaurantId,
            $request->validated('name'),
            $request->validated('active') ?? true,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
