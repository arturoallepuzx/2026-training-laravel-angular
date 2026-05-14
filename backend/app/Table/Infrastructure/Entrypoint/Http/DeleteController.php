<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\DeleteTable\DeleteTable;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteTable $deleteTable,
    ) {}

    public function __invoke(string $restaurantId, string $tableId): JsonResponse
    {
        ($this->deleteTable)($tableId, $restaurantId);

        return new JsonResponse(null, 204);
    }
}
