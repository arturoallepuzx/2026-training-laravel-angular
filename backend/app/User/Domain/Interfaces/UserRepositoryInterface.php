<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function create(User $user): void;

    public function findById(Uuid $id, Uuid $restaurantId): ?User;

    public function findByEmail(Email $email, Uuid $restaurantId): ?User;
}
