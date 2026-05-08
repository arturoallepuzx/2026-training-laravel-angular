<?php

declare(strict_types=1);

namespace App\Restaurant\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class RestaurantEmailAlreadyExistsException extends ConflictException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('A restaurant with email "%s" already exists.', $email));
    }
}
