<?php

declare(strict_types=1);

namespace App\Tax\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class TaxNameAlreadyExistsException extends ConflictException
{
    public static function forName(string $name): self
    {
        return new self(sprintf('A tax with name "%s" already exists for this restaurant.', $name));
    }
}
