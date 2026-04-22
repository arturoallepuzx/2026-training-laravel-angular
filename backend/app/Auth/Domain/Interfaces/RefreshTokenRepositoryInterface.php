<?php

declare(strict_types=1);

namespace App\Auth\Domain\Interfaces;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\ValueObject\RefreshTokenHash;

interface RefreshTokenRepositoryInterface
{
    public function create(RefreshToken $refreshToken): void;

    public function findByTokenHash(RefreshTokenHash $tokenHash): ?RefreshToken;

    public function update(RefreshToken $refreshToken): void;
}
