<?php

namespace App\Auth\Domain\Interfaces;

use App\Auth\Domain\ValueObject\IssuedRefreshToken;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

interface RefreshTokenIssuerInterface
{
    public function issue(Uuid $userId, Uuid $sessionId, DomainDateTime $expiresAt): IssuedRefreshToken;
}
