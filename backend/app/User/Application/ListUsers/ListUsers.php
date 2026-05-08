<?php

declare(strict_types=1);

namespace App\User\Application\ListUsers;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class ListUsers
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $restaurantId): ListUsersResponse
    {
        $users = $this->userRepository->findAllByRestaurantId(Uuid::create($restaurantId));

        return ListUsersResponse::create($users);
    }
}
