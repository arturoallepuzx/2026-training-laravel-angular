<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface UserAuthenticationRevokerInterface
{
    public function revokeForRestaurant(Uuid $restaurantId, string $refreshCredential): void;
}
