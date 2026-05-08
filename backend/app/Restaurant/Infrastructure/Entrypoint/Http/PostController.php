<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\CreateRestaurantWithAdmin\CreateRestaurantWithAdmin;
use App\Restaurant\Infrastructure\Entrypoint\Http\Requests\CreateRestaurantRequest;
use Illuminate\Http\JsonResponse;

class PostController
{
    public function __construct(
        private CreateRestaurantWithAdmin $createRestaurantWithAdmin,
    ) {}

    public function __invoke(CreateRestaurantRequest $request): JsonResponse
    {
        $response = ($this->createRestaurantWithAdmin)(
            $request->validated('name'),
            $request->validated('legal_name'),
            $request->validated('tax_id'),
            $request->validated('email'),
            $request->validated('admin_name'),
            $request->validated('admin_email'),
            $request->validated('admin_password'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
