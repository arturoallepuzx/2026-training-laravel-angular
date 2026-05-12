<?php

declare(strict_types=1);

namespace App\Zone\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class ZoneNotFoundException extends NotFoundException
{
    public static function forId(Uuid $id): self
    {
        return new self(sprintf('Zone with id "%s" not found.', $id->value()));
    }
}
