<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\UpdateTable\UpdateTable;
use App\Table\Infrastructure\Entrypoint\Http\Requests\UpdateTableRequest;
use Illuminate\Http\JsonResponse;

class PutController
{
    public function __construct(
        private UpdateTable $updateTable,
    ) {}

    public function __invoke(UpdateTableRequest $request, string $restaurantId, string $tableId): JsonResponse
    {
        $validated = $request->validated();

        $response = ($this->updateTable)(
            $tableId,
            $restaurantId,
            $validated['zone_id'] ?? null,
            $validated['name'] ?? null,
        );

        return new JsonResponse($response->toArray());
    }
}
