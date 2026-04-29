<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

class MissingAuthContextException extends UnauthorizedException
{
    public static function missing(): self
    {
        return new self('Missing authentication context.');
    }
}
