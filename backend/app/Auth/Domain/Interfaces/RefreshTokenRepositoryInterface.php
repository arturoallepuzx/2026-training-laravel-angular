<?php

declare(strict_types=1);

namespace App\Auth\Domain\Interfaces;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\ValueObject\RefreshTokenHash;
use App\Shared\Domain\ValueObject\Uuid;

interface RefreshTokenRepositoryInterface
{
    public function create(RefreshToken $refreshToken): void;

    public function findByTokenHash(RefreshTokenHash $tokenHash): ?RefreshToken;

    public function update(RefreshToken $refreshToken): void;

    public function revokeAllInSession(Uuid $sessionId): void;
}
