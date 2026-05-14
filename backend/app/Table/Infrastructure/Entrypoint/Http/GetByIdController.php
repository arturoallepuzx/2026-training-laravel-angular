<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\GetTableById\GetTableById;
use Illuminate\Http\JsonResponse;

class GetByIdController
{
    public function __construct(
        private GetTableById $getTableById,
    ) {}

    public function __invoke(string $restaurantId, string $tableId): JsonResponse
    {
        $response = ($this->getTableById)($tableId, $restaurantId);

        return new JsonResponse($response->toArray());
    }
}
