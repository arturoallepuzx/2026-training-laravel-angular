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
        $validated = $request->validated();

        $response = ($this->updateUser)(
            $userId,
            $restaurantId,
            $validated['name'] ?? null,
            $validated['email'] ?? null,
            $validated['role'] ?? null,
            $validated['image_src'] ?? null,
            array_key_exists('image_src', $validated),
        );

        return new JsonResponse($response->toArray());
    }
}
