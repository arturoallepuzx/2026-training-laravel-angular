<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class ProductNameAlreadyExistsException extends ConflictException
{
    public static function forName(string $name): self
    {
        return new self(sprintf('A product with name "%s" already exists for this restaurant.', $name));
    }
}
