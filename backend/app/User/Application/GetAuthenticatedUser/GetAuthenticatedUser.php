<?php

declare(strict_types=1);

namespace App\User\Application\GetAuthenticatedUser;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetAuthenticatedUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $restaurantId, string $userId): GetAuthenticatedUserResponse
    {
        $user = $this->userRepository->findById(
            Uuid::create($userId),
            Uuid::create($restaurantId),
        );

        if ($user === null) {
            throw AuthenticationRequiredException::invalid();
        }

        return GetAuthenticatedUserResponse::create($user);
    }
}
