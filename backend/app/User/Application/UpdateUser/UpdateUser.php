<?php

declare(strict_types=1);

namespace App\User\Application\UpdateUser;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserName;

class UpdateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(
        string $id,
        string $restaurantId,
        ?string $name,
        ?string $email,
        ?string $role,
        ?string $imageSrc,
        bool $imageSrcProvided,
    ): UpdateUserResponse {
        $userId = Uuid::create($id);
        $restaurantUuid = Uuid::create($restaurantId);

        $user = $this->userRepository->findById($userId, $restaurantUuid);

        if ($user === null) {
            throw UserNotFoundException::forIdInRestaurant($userId, $restaurantUuid);
        }

        if ($name !== null) {
            $user->updateName(UserName::create($name));
        }

        if ($email !== null) {
            $newEmail = Email::create($email);

            if ($newEmail->value() !== $user->email()->value()) {
                if ($this->userRepository->existsByEmailExcludingId($newEmail, $user->id())) {
                    throw UserEmailAlreadyExistsException::forEmail($newEmail->value());
                }

                $user->updateEmail($newEmail);
            }
        }

        if ($role !== null) {
            $user->updateRole(UserRole::createTenantRole($role));
        }

        if ($imageSrcProvided) {
            $user->updateImageSrc($imageSrc);
        }

        if ($user->wasModified()) {
            $this->userRepository->update($user);
        }

        return UpdateUserResponse::create($user);
    }
}
