<?php

declare(strict_types=1);

namespace App\Product\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface ProductTaxExistsCheckerInterface
{
    public function check(Uuid $taxId, Uuid $restaurantId): bool;
}
