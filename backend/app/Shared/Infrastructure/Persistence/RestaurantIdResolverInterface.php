<?php

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\ValueObject\Uuid;

interface RestaurantIdResolverInterface
{
    public function toInternalId(Uuid $restaurantUuid): int;
}
