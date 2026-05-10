<?php

declare(strict_types=1);

namespace App\User\Application\CreateUser;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\PinHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserName;

class CreateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private PinHasherInterface $pinHasher,
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
        $emailVo = Email::create($email);

        if ($this->userRepository->existsByEmail($emailVo)) {
            throw UserEmailAlreadyExistsException::forEmail($emailVo->value());
        }

        $user = User::dddCreate(
            Uuid::create($restaurantId),
            UserRole::createTenantRole($role),
            UserName::create($name),
            $emailVo,
            $this->passwordHasher->hash($plainPassword),
            $pin !== null ? $this->pinHasher->hash($pin) : null,
            $imageSrc,
        );

        $this->userRepository->create($user);

        return CreateUserResponse::create($user);
    }
}
