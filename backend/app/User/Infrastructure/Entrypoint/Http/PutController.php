<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\UpdateUser\UpdateUser;
use App\User\Infrastructure\Entrypoint\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;

class PutController
{
    public function __construct(
        private UpdateUser $updateUser,
    ) {}

    public function __invoke(UpdateUserRequest $request, string $restaurantId, string $userId): JsonResponse
    {
        $response = ($this->updateUser)(
            $userId,
            $restaurantId,
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('role'),
            $request->validated('image_src'),
            $request->has('image_src'),
        );

        return new JsonResponse($response->toArray());
    }
}
