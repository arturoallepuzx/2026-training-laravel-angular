<?php

declare(strict_types=1);

namespace App\Restaurant\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class RestaurantNotFoundException extends NotFoundException
{
    public static function forId(Uuid $id): self
    {
        return new self(sprintf('Restaurant "%s" not found.', $id->value()));
    }
}
