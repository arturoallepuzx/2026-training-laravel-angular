<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use PHPUnit\Framework\TestCase;

class RefreshTokenSecretTest extends TestCase
{
    private const VALID_SECRET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ'; // 43 chars

    public function test_creates_with_valid_base64url_43_chars(): void
    {
        $secret = RefreshTokenSecret::create(self::VALID_SECRET);

        $this->assertSame(self::VALID_SECRET, $secret->value());
    }

    public function test_accepts_underscore_and_dash(): void
    {
        $value = str_repeat('a', 41).'_-'; // 43 chars with base64url specials

        $secret = RefreshTokenSecret::create($value);

        $this->assertSame($value, $secret->value());
    }

    public function test_rejects_too_short(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RefreshTokenSecret::create(str_repeat('a', 42));
    }

    public function test_rejects_too_long(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RefreshTokenSecret::create(str_repeat('a', 44));
    }

    public function test_rejects_invalid_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // 43 chars but contains '+' '/' '=' which are base64 (not base64url)
        RefreshTokenSecret::create(str_repeat('a', 40).'+/=');
    }

    public function test_hash_returns_sha256_hex_of_value(): void
    {
        $secret = RefreshTokenSecret::create(self::VALID_SECRET);

        $hash = $secret->hash();

        $this->assertSame(64, strlen($hash));
        $this->assertTrue(ctype_xdigit($hash));
        $this->assertSame(hash('sha256', self::VALID_SECRET), $hash);
    }

    public function test_same_value_produces_same_hash(): void
    {
        $a = RefreshTokenSecret::create(self::VALID_SECRET);
        $b = RefreshTokenSecret::create(self::VALID_SECRET);

        $this->assertSame($a->hash(), $b->hash());
    }

    public function test_equals_compares_by_value(): void
    {
        $other = str_repeat('b', 43);
        $a = RefreshTokenSecret::create(self::VALID_SECRET);
        $b = RefreshTokenSecret::create(self::VALID_SECRET);
        $c = RefreshTokenSecret::create($other);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
