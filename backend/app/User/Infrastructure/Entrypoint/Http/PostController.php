<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\CreateUser\CreateUser;
use App\User\Infrastructure\Entrypoint\Http\Requests\CreateUserRequest;
use Illuminate\Http\JsonResponse;

// TODO(step 13): add middleware auth.access_token + tenant.matches + role:admin
class PostController
{
    public function __construct(
        private CreateUser $createUser,
    ) {}

    public function __invoke(CreateUserRequest $request, string $restaurantId): JsonResponse
    {
        $response = ($this->createUser)(
            $restaurantId,
            $request->validated('role'),
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('pin'),
            $request->validated('image_src'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
