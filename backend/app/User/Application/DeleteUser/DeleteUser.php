<?php

declare(strict_types=1);

namespace App\User\Application\DeleteUser;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class DeleteUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserAuthenticationGlobalRevokerInterface $authenticationRevoker,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $id, string $restaurantId): void
    {
        $userId = Uuid::create($id);
        $restaurantUuid = Uuid::create($restaurantId);

        $user = $this->userRepository->findById($userId, $restaurantUuid);

        if ($user === null) {
            throw UserNotFoundException::forIdInRestaurant($userId, $restaurantUuid);
        }

        $this->transactionRunner->run(function () use ($userId, $restaurantUuid): void {
            $this->authenticationRevoker->revokeAllByUserId($userId);
            $this->userRepository->delete($userId, $restaurantUuid);
        });
    }
}
