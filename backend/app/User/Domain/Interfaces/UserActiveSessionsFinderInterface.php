<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\UserActiveSessionsSummary;

interface UserActiveSessionsFinderInterface
{
    /**
     * @return list<UserActiveSessionsSummary>
     */
    public function findUsersWithActiveSessionsByRestaurantId(Uuid $restaurantId): array;
}
