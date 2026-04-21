<?php

namespace Tests\Unit\Auth;

use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

class AccessTokenPayloadTest extends TestCase
{
    public function test_creates_with_valid_fields_and_getters_return_vos(): void
    {
        $userId = Uuid::generate();
        $restaurantId = Uuid::generate();
        $role = UserRole::admin();
        $sessionId = Uuid::generate();
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2026-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2026-01-01 10:15:00'));

        $payload = AccessTokenPayload::create($userId, $restaurantId, $role, $sessionId, $issuedAt, $expiresAt);

        $this->assertSame($userId->value(), $payload->userId()->value());
        $this->assertSame($restaurantId->value(), $payload->restaurantId()->value());
        $this->assertTrue($payload->role()->isAdmin());
        $this->assertSame($sessionId->value(), $payload->sessionId()->value());
        $this->assertEquals($issuedAt->value(), $payload->issuedAt()->value());
        $this->assertEquals($expiresAt->value(), $payload->expiresAt()->value());
    }

    public function test_throws_when_expires_at_equals_issued_at(): void
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2026-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2026-01-01 10:00:00'));

        $this->expectException(\InvalidArgumentException::class);

        AccessTokenPayload::create(
            Uuid::generate(),
            Uuid::generate(),
            UserRole::admin(),
            Uuid::generate(),
            $issuedAt,
            $expiresAt,
        );
    }

    public function test_throws_when_expires_at_is_before_issued_at(): void
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2026-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2026-01-01 09:59:59'));

        $this->expectException(\InvalidArgumentException::class);

        AccessTokenPayload::create(
            Uuid::generate(),
            Uuid::generate(),
            UserRole::admin(),
            Uuid::generate(),
            $issuedAt,
            $expiresAt,
        );
    }
}
