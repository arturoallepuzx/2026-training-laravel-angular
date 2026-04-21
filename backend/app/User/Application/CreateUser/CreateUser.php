<?php

namespace App\User\Application\CreateUser;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserPin;
use App\User\Domain\ValueObject\UserRole;

class CreateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $role,
        string $name,
        string $email,
        string $plainPassword,
        ?string $pin = null,
        ?string $imageSrc = null,
    ): CreateUserResponse {
        $user = User::dddCreate(
            Uuid::create($restaurantId),
            UserRole::create($role),
            UserName::create($name),
            Email::create($email),
            $this->passwordHasher->hash($plainPassword),
            $pin !== null ? UserPin::create($pin) : null,
            $imageSrc,
        );

        $this->userRepository->create($user);

        return CreateUserResponse::create($user);
    }
}
