<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Application\CreateSuperadminUser\CreateSuperadminUser;
use App\User\Infrastructure\Entrypoint\Http\Requests\CreateSuperadminUserRequest;
use Illuminate\Http\JsonResponse;

class SuperadminPostController
{
    public function __construct(
        private CreateSuperadminUser $createSuperadminUser,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(CreateSuperadminUserRequest $request): JsonResponse
    {
        $superadminRestaurant = $this->restaurantRepository->findByEmail(
            Email::create((string) config('superadmin.restaurant_email'))
        );

        if ($superadminRestaurant === null) {
            throw new \RuntimeException('Superadmin restaurant is not seeded.');
        }

        $response = ($this->createSuperadminUser)(
            $superadminRestaurant->id()->value(),
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
