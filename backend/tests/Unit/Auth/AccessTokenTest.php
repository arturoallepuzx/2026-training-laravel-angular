<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\Domain\ValueObject\AccessToken;
use App\Shared\Domain\ValueObject\DomainDateTime;
use PHPUnit\Framework\TestCase;

class AccessTokenTest extends TestCase
{
    public function test_creates_with_value_and_expires_at(): void
    {
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2026-01-01 10:15:00'));

        $token = AccessToken::create('jwt.access.token', $expiresAt);

        $this->assertSame('jwt.access.token', $token->value());
        $this->assertEquals($expiresAt->value(), $token->expiresAt()->value());
    }

    public function test_throws_when_value_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AccessToken::create('', DomainDateTime::create(new \DateTimeImmutable('2026-01-01 10:15:00')));
    }
}
