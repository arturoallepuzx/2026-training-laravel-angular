<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\IssuedAuthentication;

interface UserAuthenticationRefresherInterface
{
    public function refreshForRestaurant(Uuid $restaurantId, string $refreshCredential): IssuedAuthentication;
}
