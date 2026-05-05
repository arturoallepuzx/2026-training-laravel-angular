<?php

declare(strict_types=1);

namespace App\User\Application\ListUsersWithActiveSessions;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserActiveSessionsFinderInterface;

class ListUsersWithActiveSessions
{
    public function __construct(
        private UserActiveSessionsFinderInterface $userActiveSessionsFinder,
    ) {}

    public function __invoke(string $restaurantId): ListUsersWithActiveSessionsResponse
    {
        return ListUsersWithActiveSessionsResponse::create(
            $this->userActiveSessionsFinder->findUsersWithActiveSessionsByRestaurantId(
                Uuid::create($restaurantId)
            )
        );
    }
}
