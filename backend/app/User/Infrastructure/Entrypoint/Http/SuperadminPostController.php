<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\CreateSuperadminUser\CreateSuperadminUser;
use App\User\Infrastructure\Entrypoint\Http\Requests\CreateSuperadminUserRequest;
use Illuminate\Http\JsonResponse;

class SuperadminPostController
{
    public function __construct(
        private CreateSuperadminUser $createSuperadminUser,
    ) {}

    public function __invoke(CreateSuperadminUserRequest $request): JsonResponse
    {
        $response = ($this->createSuperadminUser)(
            (string) config('superadmin.restaurant_uuid'),
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
