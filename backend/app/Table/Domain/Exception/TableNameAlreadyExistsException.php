<?php

declare(strict_types=1);

namespace App\Table\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class TableNameAlreadyExistsException extends ConflictException
{
    public static function forName(string $name): self
    {
        return new self(sprintf('A table with name "%s" already exists in this zone.', $name));
    }
}
