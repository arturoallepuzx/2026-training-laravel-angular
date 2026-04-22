<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure;

use App\Auth\Domain\ValueObject\IssuedRefreshToken;
use App\Auth\Infrastructure\Services\RandomRefreshTokenIssuer;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class RandomRefreshTokenIssuerTest extends TestCase
{
    public function test_issue_returns_issued_refresh_token_bound_to_user_session_and_expiration(): void
    {
        $userId = Uuid::generate();
        $sessionId = Uuid::generate();
        $expiresAt = DomainDateTime::create((new \DateTimeImmutable)->modify('+30 days'));

        $issued = (new RandomRefreshTokenIssuer)->issue($userId, $sessionId, $expiresAt);

        $this->assertInstanceOf(IssuedRefreshToken::class, $issued);
        $this->assertSame($userId->value(), $issued->entity()->userId()->value());
        $this->assertSame($sessionId->value(), $issued->entity()->sessionId()->value());
        $this->assertEquals($expiresAt->value(), $issued->entity()->expiresAt()->value());
    }

    public function test_issue_entity_token_hash_matches_secret_hash(): void
    {
        $issuer = new RandomRefreshTokenIssuer;

        $issued = $issuer->issue(
            Uuid::generate(),
            Uuid::generate(),
            DomainDateTime::create((new \DateTimeImmutable)->modify('+30 days')),
        );

        $this->assertTrue(
            $issued->secret()->hash()->equals($issued->entity()->tokenHash())
        );
    }

    public function test_issue_generates_base64url_secret_with_43_chars(): void
    {
        $issued = (new RandomRefreshTokenIssuer)->issue(
            Uuid::generate(),
            Uuid::generate(),
            DomainDateTime::create((new \DateTimeImmutable)->modify('+30 days')),
        );

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_\-]{43}$/', $issued->secret()->value());
    }

    public function test_issue_generates_different_secrets_on_each_call(): void
    {
        $issuer = new RandomRefreshTokenIssuer;
        $userId = Uuid::generate();
        $sessionId = Uuid::generate();
        $expiresAt = DomainDateTime::create((new \DateTimeImmutable)->modify('+30 days'));

        $first = $issuer->issue($userId, $sessionId, $expiresAt);
        $second = $issuer->issue($userId, $sessionId, $expiresAt);

        $this->assertNotSame($first->secret()->value(), $second->secret()->value());
        $this->assertNotSame($first->entity()->id()->value(), $second->entity()->id()->value());
    }
}
