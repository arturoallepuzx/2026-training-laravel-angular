<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\ListUsersWithActiveSessions\ListUsersWithActiveSessions;
use App\User\Infrastructure\Entrypoint\Http\Requests\TenantRouteRequest;
use Illuminate\Http\JsonResponse;

class GetUsersWithActiveSessionsController
{
    public function __construct(
        private ListUsersWithActiveSessions $listUsersWithActiveSessions,
    ) {}

    public function __invoke(TenantRouteRequest $request, string $restaurantId): JsonResponse
    {
        $response = ($this->listUsersWithActiveSessions)($restaurantId);

        return new JsonResponse($response->toArray(), 200);
    }
}
