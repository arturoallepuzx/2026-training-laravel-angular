<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetAuthenticatedUser\GetAuthenticatedUser;
use Illuminate\Http\JsonResponse;

class GetMeController
{
    public function __construct(
        private GetAuthenticatedUser $getAuthenticatedUser,
    ) {}

    public function __invoke(string $restaurantId): JsonResponse
    {
        $response = ($this->getAuthenticatedUser)($restaurantId);

        return new JsonResponse($response->toArray(), 200);
    }
}
