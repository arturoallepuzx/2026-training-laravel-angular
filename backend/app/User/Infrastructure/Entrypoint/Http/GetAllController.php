<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\ListUsers\ListUsers;
use Illuminate\Http\JsonResponse;

class GetAllController
{
    public function __construct(
        private ListUsers $listUsers,
    ) {}

    public function __invoke(string $restaurantId): JsonResponse
    {
        $response = ($this->listUsers)($restaurantId);

        return new JsonResponse($response->toArray());
    }
}
