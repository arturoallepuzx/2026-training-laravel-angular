<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\User\Domain\ValueObject\UserPinHash;

interface PinHasherInterface
{
    public function hash(string $plainPin): UserPinHash;

    public function verify(string $plainPin, UserPinHash $pinHash): bool;
}
