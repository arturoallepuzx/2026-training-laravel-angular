<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\ListTables\ListTables;
use Illuminate\Http\JsonResponse;

class GetAllController
{
    public function __construct(
        private ListTables $listTables,
    ) {}

    public function __invoke(string $restaurantId): JsonResponse
    {
        $response = ($this->listTables)($restaurantId);

        return new JsonResponse($response->toArray());
    }
}
