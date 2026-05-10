<?php

declare(strict_types=1);

namespace Tests\Unit\User\Infrastructure;

use App\User\Domain\ValueObject\UserPinHash;
use App\User\Infrastructure\Services\LaravelPinHasher;
use Tests\TestCase;

class LaravelPinHasherTest extends TestCase
{
    public function test_hash_returns_pin_hash_that_can_be_verified(): void
    {
        $hasher = new LaravelPinHasher;

        $hash = $hasher->hash('1234');

        $this->assertInstanceOf(UserPinHash::class, $hash);
        $this->assertNotSame('1234', $hash->value());
        $this->assertTrue($hasher->verify('1234', $hash));
        $this->assertFalse($hasher->verify('9999', $hash));
    }
}
