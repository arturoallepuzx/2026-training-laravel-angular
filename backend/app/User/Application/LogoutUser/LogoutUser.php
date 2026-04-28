<?php

declare(strict_types=1);

namespace App\User\Application\LogoutUser;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserAuthenticationRevokerInterface;

class LogoutUser
{
    public function __construct(
        private UserAuthenticationRevokerInterface $userAuthenticationRevoker,
    ) {}

    public function __invoke(string $restaurantId, string $refreshCredential): void
    {
        $this->userAuthenticationRevoker->revokeForRestaurant(
            Uuid::create($restaurantId),
            $refreshCredential,
        );
    }
}
