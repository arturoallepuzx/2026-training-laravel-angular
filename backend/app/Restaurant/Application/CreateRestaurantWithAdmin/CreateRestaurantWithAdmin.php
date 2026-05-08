<?php

declare(strict_types=1);

namespace App\Restaurant\Application\CreateRestaurantWithAdmin;

use App\Restaurant\Application\CreateRestaurant\CreateRestaurant;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserName;

class CreateRestaurantWithAdmin
{
    public function __construct(
        private CreateRestaurant $createRestaurant,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(
        string $restaurantName,
        string $legalName,
        string $taxId,
        string $restaurantEmail,
        string $adminName,
        string $adminEmail,
        string $adminPlainPassword,
    ): CreateRestaurantWithAdminResponse {
        $adminEmailVo = Email::create($adminEmail);

        if ($this->userRepository->existsByEmail($adminEmailVo)) {
            throw UserEmailAlreadyExistsException::forEmail($adminEmailVo->value());
        }

        return $this->transactionRunner->run(function () use (
            $restaurantName,
            $legalName,
            $taxId,
            $restaurantEmail,
            $adminName,
            $adminEmailVo,
            $adminPlainPassword,
        ): CreateRestaurantWithAdminResponse {
            $restaurantResponse = ($this->createRestaurant)(
                $restaurantName,
                $legalName,
                $taxId,
                $restaurantEmail,
            );

            $admin = User::dddCreate(
                Uuid::create($restaurantResponse->id),
                UserRole::admin(),
                UserName::create($adminName),
                $adminEmailVo,
                $this->passwordHasher->hash($adminPlainPassword),
                null,
                null,
            );

            $this->userRepository->create($admin);

            return CreateRestaurantWithAdminResponse::create($restaurantResponse, $admin);
        });
    }
}
