<?php

declare(strict_types=1);

namespace App\Table\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class TableNotFoundException extends NotFoundException
{
    public static function forId(Uuid $id): self
    {
        return new self(sprintf('Table with id "%s" not found.', $id->value()));
    }
}
