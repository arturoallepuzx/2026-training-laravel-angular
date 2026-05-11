<?php

declare(strict_types=1);

namespace App\Family\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class FamilyNotFoundException extends NotFoundException
{
    public static function forId(Uuid $id): self
    {
        return new self(sprintf('Family with id "%s" not found.', $id->value()));
    }
}
