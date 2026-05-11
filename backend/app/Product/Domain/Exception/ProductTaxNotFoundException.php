<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class ProductTaxNotFoundException extends NotFoundException
{
    public static function forId(Uuid $id): self
    {
        return new self(sprintf('Tax with id "%s" not found for this restaurant.', $id->value()));
    }
}
