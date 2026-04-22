<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure;

use App\Auth\Domain\Exception\ExpiredAccessTokenException;
use App\Auth\Domain\Exception\InvalidAccessTokenException;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Auth\Infrastructure\Services\FirebaseJwtAccessTokenIssuer;
use App\Auth\Infrastructure\Services\FirebaseJwtAccessTokenVerifier;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\UserRole;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

class FirebaseJwtAccessTokenVerifierTest extends TestCase
{
    private const SECRET = 'test-secret-at-least-32-bytes-long-xxxx';

    private const OTHER_SECRET = 'another-secret-at-least-32-bytes-zzzz';

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_verify_returns_payload_for_valid_token(): void
    {
        $userId = Uuid::generate();
        $restaurantId = Uuid::generate();
        $sessionId = Uuid::generate();
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        $token = (new FirebaseJwtAccessTokenIssuer(self::SECRET))->issue(
            AccessTokenPayload::create(
                $userId,
                $restaurantId,
                UserRole::supervisor(),
                $sessionId,
                $issuedAt,
                $expiresAt,
            )
        )->value();

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;

        $payload = (new FirebaseJwtAccessTokenVerifier(self::SECRET))->verify($token);

        $this->assertSame($userId->value(), $payload->userId()->value());
        $this->assertSame($restaurantId->value(), $payload->restaurantId()->value());
        $this->assertTrue($payload->role()->isSupervisor());
        $this->assertSame($sessionId->value(), $payload->sessionId()->value());
        $this->assertEquals($issuedAt->value()->getTimestamp(), $payload->issuedAt()->value()->getTimestamp());
        $this->assertEquals($expiresAt->value()->getTimestamp(), $payload->expiresAt()->value()->getTimestamp());
    }

    public function test_verify_throws_expired_when_token_is_expired(): void
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        $token = (new FirebaseJwtAccessTokenIssuer(self::SECRET))->issue(
            AccessTokenPayload::create(
                Uuid::generate(),
                Uuid::generate(),
                UserRole::admin(),
                Uuid::generate(),
                $issuedAt,
                $expiresAt,
            )
        )->value();

        JWT::$timestamp = $expiresAt->value()->getTimestamp() + 60;

        $this->expectException(ExpiredAccessTokenException::class);

        (new FirebaseJwtAccessTokenVerifier(self::SECRET))->verify($token);
    }

    public function test_verify_throws_invalid_when_signature_does_not_match(): void
    {
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        $token = (new FirebaseJwtAccessTokenIssuer(self::SECRET))->issue(
            AccessTokenPayload::create(
                Uuid::generate(),
                Uuid::generate(),
                UserRole::admin(),
                Uuid::generate(),
                $issuedAt,
                $expiresAt,
            )
        )->value();

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;

        $this->expectException(InvalidAccessTokenException::class);

        (new FirebaseJwtAccessTokenVerifier(self::OTHER_SECRET))->verify($token);
    }

    public function test_verify_throws_invalid_when_token_is_malformed(): void
    {
        $this->expectException(InvalidAccessTokenException::class);

        (new FirebaseJwtAccessTokenVerifier(self::SECRET))->verify('not-a-jwt');
    }

    public function test_constructor_throws_when_secret_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FirebaseJwtAccessTokenVerifier('');
    }
}
