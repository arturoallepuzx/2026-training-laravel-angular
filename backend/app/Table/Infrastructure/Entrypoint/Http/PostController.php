<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\CreateTable\CreateTable;
use App\Table\Infrastructure\Entrypoint\Http\Requests\TableRequest;
use Illuminate\Http\JsonResponse;

class PostController
{
    public function __construct(
        private CreateTable $createTable,
    ) {}

    public function __invoke(TableRequest $request, string $restaurantId): JsonResponse
    {
        $response = ($this->createTable)(
            $restaurantId,
            $request->validated('zone_id'),
            $request->validated('name'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
