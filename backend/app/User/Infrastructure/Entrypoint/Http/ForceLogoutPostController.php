<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\ForceLogoutUser\ForceLogoutUser;
use App\User\Infrastructure\Entrypoint\Http\Requests\TenantRouteRequest;
use Illuminate\Http\Response;

class ForceLogoutPostController
{
    public function __construct(
        private ForceLogoutUser $forceLogoutUser,
    ) {}

    public function __invoke(TenantRouteRequest $request, string $restaurantId, string $userId): Response
    {
        ($this->forceLogoutUser)($restaurantId, $userId);

        return new Response('', 204);
    }
}
