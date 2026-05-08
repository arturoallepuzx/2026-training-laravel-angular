<?php

declare(strict_types=1);

namespace App\User\Application\CreateSuperadminUser;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserName;

class CreateSuperadminUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(
        string $superadminRestaurantId,
        string $name,
        string $email,
        string $plainPassword,
    ): CreateSuperadminUserResponse {
        $emailVo = Email::create($email);

        if ($this->userRepository->existsByEmail($emailVo)) {
            throw UserEmailAlreadyExistsException::forEmail($emailVo->value());
        }

        $user = User::dddCreate(
            Uuid::create($superadminRestaurantId),
            UserRole::superadmin(),
            UserName::create($name),
            $emailVo,
            $this->passwordHasher->hash($plainPassword),
            null,
            null,
        );

        $this->userRepository->create($user);

        return CreateSuperadminUserResponse::create($user);
    }
}
