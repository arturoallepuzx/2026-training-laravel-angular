<?php

declare(strict_types=1);

namespace App\Zone\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class ZoneNameAlreadyExistsException extends ConflictException
{
    public static function forName(string $name): self
    {
        return new self(sprintf('A zone with name "%s" already exists for this restaurant.', $name));
    }
}
