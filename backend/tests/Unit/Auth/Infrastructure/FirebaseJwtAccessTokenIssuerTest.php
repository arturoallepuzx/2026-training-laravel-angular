<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure;

use App\Auth\Domain\ValueObject\AccessToken;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Auth\Infrastructure\Services\FirebaseJwtAccessTokenIssuer;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\UserRole;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;

class FirebaseJwtAccessTokenIssuerTest extends TestCase
{
    private const SECRET = 'test-secret-at-least-32-bytes-long-xxxx';

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
        parent::tearDown();
    }

    public function test_issue_returns_access_token_with_encoded_claims(): void
    {
        $userId = Uuid::generate();
        $restaurantId = Uuid::generate();
        $sessionId = Uuid::generate();
        $issuedAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:00:00'));
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('2099-01-01 10:15:00'));

        $payload = AccessTokenPayload::create(
            $userId,
            $restaurantId,
            UserRole::admin(),
            $sessionId,
            $issuedAt,
            $expiresAt,
        );

        $issuer = new FirebaseJwtAccessTokenIssuer(self::SECRET);
        $accessToken = $issuer->issue($payload);

        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $this->assertEquals($expiresAt->value(), $accessToken->expiresAt()->value());

        JWT::$timestamp = $issuedAt->value()->getTimestamp() + 60;
        $decoded = JWT::decode($accessToken->value(), new Key(self::SECRET, 'HS256'));

        $this->assertSame($userId->value(), $decoded->sub);
        $this->assertSame($restaurantId->value(), $decoded->restaurant_id);
        $this->assertSame('admin', $decoded->role);
        $this->assertSame($sessionId->value(), $decoded->session_id);
        $this->assertSame($issuedAt->value()->getTimestamp(), $decoded->iat);
        $this->assertSame($expiresAt->value()->getTimestamp(), $decoded->exp);
    }

    public function test_constructor_throws_when_secret_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FirebaseJwtAccessTokenIssuer('');
    }
}
