<?php

declare(strict_types=1);

namespace App\Product\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface ProductFamilyExistsCheckerInterface
{
    public function check(Uuid $familyId, Uuid $restaurantId): bool;
}
