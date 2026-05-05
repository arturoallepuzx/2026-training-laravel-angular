<?php

declare(strict_types=1);

namespace App\User\Application\ForceLogoutUser;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class ForceLogoutUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserAuthenticationGlobalRevokerInterface $userAuthenticationGlobalRevoker,
    ) {}

    public function __invoke(string $restaurantId, string $userId): void
    {
        $restaurantUuid = Uuid::create($restaurantId);
        $userUuid = Uuid::create($userId);

        $user = $this->userRepository->findById($userUuid, $restaurantUuid);

        if ($user === null) {
            throw UserNotFoundException::forIdInRestaurant($userUuid, $restaurantUuid);
        }

        $this->userAuthenticationGlobalRevoker->revokeAllByUserId($user->id());
    }
}
