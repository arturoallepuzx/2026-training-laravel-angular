<?php

namespace App\User\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function create(User $user): void;

    public function findById(Uuid $id, Uuid $restaurantId): ?User;
}
