<?php

declare(strict_types=1);

namespace App\User\Application\GetUserById;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetUserById
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): GetUserByIdResponse
    {
        $userId = Uuid::create($id);
        $restaurantUuid = Uuid::create($restaurantId);

        $user = $this->userRepository->findById($userId, $restaurantUuid);

        if ($user === null) {
            throw UserNotFoundException::forIdInRestaurant($userId, $restaurantUuid);
        }

        return GetUserByIdResponse::create($user);
    }
}
