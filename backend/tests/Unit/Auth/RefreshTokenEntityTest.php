<?php

namespace Tests\Unit\Auth;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class RefreshTokenEntityTest extends TestCase
{
    private const VALID_SECRET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ';

    public function test_ddd_create_builds_with_fresh_lifecycle_state(): void
    {
        $userId = Uuid::generate();
        $sessionId = Uuid::generate();
        $secret = RefreshTokenSecret::create(self::VALID_SECRET);
        $expiresAt = DomainDateTime::create(new \DateTimeImmutable('+1 hour'));

        $token = RefreshToken::dddCreate($userId, $sessionId, $secret, $expiresAt);

        $this->assertSame($userId->value(), $token->userId()->value());
        $this->assertSame($sessionId->value(), $token->sessionId()->value());
        $this->assertSame($secret->hash(), $token->tokenHash());
        $this->assertEquals($expiresAt->value(), $token->expiresAt()->value());
        $this->assertNull($token->revokedAt());
        $this->assertNull($token->replacedById());
        $this->assertFalse($token->isRevoked());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $token->id()->value()
        );
    }

    public function test_ddd_create_throws_when_expires_at_is_in_the_past(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RefreshToken::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            RefreshTokenSecret::create(self::VALID_SECRET),
            DomainDateTime::create(new \DateTimeImmutable('-1 hour')),
        );
    }

    public function test_from_persistence_builds_with_nullables_null(): void
    {
        $id = Uuid::generate()->value();
        $userId = Uuid::generate()->value();
        $sessionId = Uuid::generate()->value();
        $hash = hash('sha256', self::VALID_SECRET);
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $createdAt = new \DateTimeImmutable('-1 minute');

        $token = RefreshToken::fromPersistence(
            $id, $userId, $sessionId, $hash,
            $expiresAt, null, null, $createdAt, $createdAt,
        );

        $this->assertSame($id, $token->id()->value());
        $this->assertSame($hash, $token->tokenHash());
        $this->assertNull($token->revokedAt());
        $this->assertNull($token->replacedById());
        $this->assertFalse($token->isRevoked());
    }

    public function test_from_persistence_builds_with_revoked_and_replaced(): void
    {
        $replacementId = Uuid::generate()->value();
        $revokedAt = new \DateTimeImmutable('-10 minutes');

        $token = RefreshToken::fromPersistence(
            Uuid::generate()->value(),
            Uuid::generate()->value(),
            Uuid::generate()->value(),
            hash('sha256', self::VALID_SECRET),
            new \DateTimeImmutable('+1 hour'),
            $revokedAt,
            $replacementId,
            new \DateTimeImmutable('-1 hour'),
            new \DateTimeImmutable('-10 minutes'),
        );

        $this->assertTrue($token->isRevoked());
        $this->assertEquals($revokedAt, $token->revokedAt()->value());
        $this->assertSame($replacementId, $token->replacedById()->value());
    }

    public function test_is_expired_true_when_past(): void
    {
        $token = RefreshToken::fromPersistence(
            Uuid::generate()->value(),
            Uuid::generate()->value(),
            Uuid::generate()->value(),
            hash('sha256', self::VALID_SECRET),
            new \DateTimeImmutable('-1 second'),
            null, null,
            new \DateTimeImmutable('-1 hour'),
            new \DateTimeImmutable('-1 hour'),
        );

        $this->assertTrue($token->isExpired());
    }

    public function test_is_expired_false_when_future(): void
    {
        $token = RefreshToken::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            RefreshTokenSecret::create(self::VALID_SECRET),
            DomainDateTime::create(new \DateTimeImmutable('+1 hour')),
        );

        $this->assertFalse($token->isExpired());
    }

    public function test_revoke_sets_revoked_at_and_touches_updated_at(): void
    {
        $token = $this->makeFreshToken();
        $initialUpdatedAt = $token->updatedAt()->value();

        usleep(1000);
        $token->revoke();

        $this->assertTrue($token->isRevoked());
        $this->assertNotNull($token->revokedAt());
        $this->assertGreaterThan($initialUpdatedAt, $token->updatedAt()->value());
    }

    public function test_revoke_is_idempotent(): void
    {
        $token = $this->makeFreshToken();
        $token->revoke();
        $firstRevokedAt = $token->revokedAt()->value();
        $firstUpdatedAt = $token->updatedAt()->value();

        usleep(1000);
        $token->revoke();

        $this->assertEquals($firstRevokedAt, $token->revokedAt()->value());
        $this->assertEquals($firstUpdatedAt, $token->updatedAt()->value());
    }

    public function test_mark_replaced_by_sets_replaced_and_revokes_and_touches(): void
    {
        $token = $this->makeFreshToken();
        $initialUpdatedAt = $token->updatedAt()->value();
        $replacementId = Uuid::generate();

        usleep(1000);
        $token->markReplacedBy($replacementId);

        $this->assertSame($replacementId->value(), $token->replacedById()->value());
        $this->assertTrue($token->isRevoked());
        $this->assertNotNull($token->revokedAt());
        $this->assertGreaterThan($initialUpdatedAt, $token->updatedAt()->value());
    }

    public function test_mark_replaced_by_preserves_original_revoked_at_when_already_revoked(): void
    {
        $token = $this->makeFreshToken();
        $token->revoke();
        $originalRevokedAt = $token->revokedAt()->value();

        usleep(1000);
        $token->markReplacedBy(Uuid::generate());

        $this->assertEquals($originalRevokedAt, $token->revokedAt()->value());
    }

    private function makeFreshToken(): RefreshToken
    {
        return RefreshToken::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            RefreshTokenSecret::create(self::VALID_SECRET),
            DomainDateTime::create(new \DateTimeImmutable('+1 hour')),
        );
    }
}
