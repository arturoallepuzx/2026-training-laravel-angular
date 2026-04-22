<?php

declare(strict_types=1);

namespace Tests\Unit\User\Infrastructure;

use App\User\Domain\ValueObject\PasswordHash;
use App\User\Infrastructure\Services\LaravelPasswordHasher;
use Tests\TestCase;

class LaravelPasswordHasherTest extends TestCase
{
    public function test_hash_returns_password_hash_value_object(): void
    {
        $hasher = new LaravelPasswordHasher;

        $hash = $hasher->hash('plain-password');

        $this->assertInstanceOf(PasswordHash::class, $hash);
    }

    public function test_verify_returns_true_when_plain_password_matches_hash(): void
    {
        $hasher = new LaravelPasswordHasher;
        $hash = $hasher->hash('plain-password');

        $this->assertTrue($hasher->verify('plain-password', $hash));
    }

    public function test_verify_returns_false_when_plain_password_does_not_match_hash(): void
    {
        $hasher = new LaravelPasswordHasher;
        $hash = $hasher->hash('plain-password');

        $this->assertFalse($hasher->verify('wrong-password', $hash));
    }
}
