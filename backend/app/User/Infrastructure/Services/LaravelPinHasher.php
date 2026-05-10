<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Services;

use App\User\Domain\Interfaces\PinHasherInterface;
use App\User\Domain\ValueObject\UserPinHash;
use Illuminate\Support\Facades\Hash;

class LaravelPinHasher implements PinHasherInterface
{
    public function hash(string $plainPin): UserPinHash
    {
        return UserPinHash::create(Hash::make($plainPin));
    }

    public function verify(string $plainPin, UserPinHash $pinHash): bool
    {
        return Hash::check($plainPin, $pinHash->value());
    }
}
