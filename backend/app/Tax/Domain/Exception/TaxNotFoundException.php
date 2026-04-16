<?php

namespace App\Tax\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class TaxNotFoundException extends NotFoundException
{
    public static function forId(Uuid $id): self
    {
        return new self(sprintf('Tax with id "%s" not found.', $id->value()));
    }
}
