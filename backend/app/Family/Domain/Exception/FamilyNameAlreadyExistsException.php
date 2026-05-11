<?php

declare(strict_types=1);

namespace App\Family\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class FamilyNameAlreadyExistsException extends ConflictException
{
    public static function forName(string $name): self
    {
        return new self(sprintf('A family with name "%s" already exists for this restaurant.', $name));
    }
}
