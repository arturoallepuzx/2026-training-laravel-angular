<?php

declare(strict_types=1);

namespace App\User\Application\GetAuthenticatedUser;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetAuthenticatedUser
{
    public function __construct(
        private AuthContextHolder $authContextHolder,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $restaurantId): GetAuthenticatedUserResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $user = $this->userRepository->findById(
            $context->userId(),
            Uuid::create($restaurantId),
        );

        if ($user === null) {
            throw AuthenticationRequiredException::invalid();
        }

        return GetAuthenticatedUserResponse::create($user);
    }
}
