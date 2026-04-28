<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationRevoker;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use Mockery;
use PHPUnit\Framework\TestCase;

class JwtUserAuthenticationRevokerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_no_op_when_secret_is_malformed(): void
    {
        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldNotReceive('findByTokenHash');
        $refreshTokenRepository->shouldNotReceive('revokeAllInSession');

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldNotReceive('findById');

        $revoker = new JwtUserAuthenticationRevoker($refreshTokenRepository, $userRepository);
        $revoker->revokeForRestaurant(Uuid::generate(), 'not-a-base64url-secret');

        $this->addToAssertionCount(1);
    }

    public function test_no_op_when_secret_is_empty(): void
    {
        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldNotReceive('findByTokenHash');
        $refreshTokenRepository->shouldNotReceive('revokeAllInSession');

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldNotReceive('findById');

        $revoker = new JwtUserAuthenticationRevoker($refreshTokenRepository, $userRepository);
        $revoker->revokeForRestaurant(Uuid::generate(), '');

        $this->addToAssertionCount(1);
    }

    public function test_no_op_when_token_not_found(): void
    {
        $secret = $this->randomSecret();

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn(null);
        $refreshTokenRepository->shouldNotReceive('revokeAllInSession');

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldNotReceive('findById');

        $revoker = new JwtUserAuthenticationRevoker($refreshTokenRepository, $userRepository);
        $revoker->revokeForRestaurant(Uuid::generate(), $secret->value());

        $this->addToAssertionCount(1);
    }

    public function test_no_op_when_token_belongs_to_different_restaurant(): void
    {
        $secret = $this->randomSecret();
        $token = $this->buildPersistedToken($secret);

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($token);
        $refreshTokenRepository->shouldNotReceive('revokeAllInSession');

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')->once()->andReturn(null);

        $revoker = new JwtUserAuthenticationRevoker($refreshTokenRepository, $userRepository);
        $revoker->revokeForRestaurant(Uuid::generate(), $secret->value());

        $this->addToAssertionCount(1);
    }

    public function test_revokes_session_when_token_belongs_to_restaurant(): void
    {
        $secret = $this->randomSecret();
        $sessionId = Uuid::generate();
        $token = $this->buildPersistedToken($secret, sessionId: $sessionId);
        $user = $this->buildUser($token->userId());

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($token);
        $refreshTokenRepository->shouldReceive('revokeAllInSession')
            ->once()
            ->with(Mockery::on(fn (Uuid $id): bool => $id->value() === $sessionId->value()));

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')
            ->once()
            ->with(
                Mockery::on(fn (Uuid $userId): bool => $userId->value() === $token->userId()->value()),
                Mockery::on(fn (Uuid $rid): bool => $rid->value() === $user->restaurantId()->value()),
            )
            ->andReturn($user);

        $revoker = new JwtUserAuthenticationRevoker($refreshTokenRepository, $userRepository);
        $revoker->revokeForRestaurant($user->restaurantId(), $secret->value());

        $this->addToAssertionCount(1);
    }

    public function test_revokes_session_even_when_token_is_already_revoked(): void
    {
        $secret = $this->randomSecret();
        $sessionId = Uuid::generate();
        $token = $this->buildPersistedToken(
            $secret,
            sessionId: $sessionId,
            revokedAt: '-5 minutes',
        );
        $user = $this->buildUser($token->userId());

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($token);
        $refreshTokenRepository->shouldReceive('revokeAllInSession')->once();

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')->once()->andReturn($user);

        $revoker = new JwtUserAuthenticationRevoker($refreshTokenRepository, $userRepository);
        $revoker->revokeForRestaurant($user->restaurantId(), $secret->value());

        $this->addToAssertionCount(1);
    }

    private function randomSecret(): RefreshTokenSecret
    {
        return RefreshTokenSecret::create(
            rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')
        );
    }

    private function buildPersistedToken(
        RefreshTokenSecret $secret,
        ?Uuid $sessionId = null,
        string $expiresAt = '+30 days',
        ?string $revokedAt = null,
    ): RefreshToken {
        return RefreshToken::fromPersistence(
            id: Uuid::generate()->value(),
            userId: Uuid::generate()->value(),
            sessionId: ($sessionId ?? Uuid::generate())->value(),
            tokenHash: $secret->hash()->value(),
            expiresAt: (new \DateTimeImmutable)->modify($expiresAt),
            revokedAt: $revokedAt !== null ? (new \DateTimeImmutable)->modify($revokedAt) : null,
            replacedById: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }

    private function buildUser(Uuid $id): User
    {
        return User::fromPersistence(
            id: $id->value(),
            restaurantId: Uuid::generate()->value(),
            role: 'admin',
            name: 'Logout User',
            email: 'logout@example.com',
            passwordHash: PasswordHash::create('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')->value(),
            pin: null,
            imageSrc: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }
}
