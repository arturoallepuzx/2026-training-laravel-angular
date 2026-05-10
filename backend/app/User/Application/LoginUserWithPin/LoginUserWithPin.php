<?php

declare(strict_types=1);

namespace App\User\Application\LoginUserWithPin;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Interfaces\PinHasherInterface;
use App\User\Domain\Interfaces\UserAuthenticationIssuerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\AuthenticationSubject;

class LoginUserWithPin
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PinHasherInterface $pinHasher,
        private UserAuthenticationIssuerInterface $userAuthenticationIssuer,
    ) {}

    public function __invoke(string $restaurantId,
        string $userId,
        string $plainPin
    ): LoginUserWithPinResponse {
        $restaurantUuid = Uuid::create($restaurantId);
        $userUuid = Uuid::create($userId);

        $user = $this->userRepository->findById($userUuid, $restaurantUuid);
        $pinHash = $user?->pinHash();

        if ($pinHash === null || ! $this->pinHasher->verify($plainPin, $pinHash)) {
            throw InvalidCredentialsException::invalid();
        }

        $issuedAuthentication = $this->userAuthenticationIssuer->issueFor(
            AuthenticationSubject::create(
                $user->id(),
                $user->restaurantId(),
                $user->role(),
            )
        );

        return LoginUserWithPinResponse::create($user, $issuedAuthentication);
    }
}
