<?php

declare(strict_types=1);

namespace App\Table\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface TableZoneExistsCheckerInterface
{
    public function check(Uuid $zoneId, Uuid $restaurantId): bool;
}
