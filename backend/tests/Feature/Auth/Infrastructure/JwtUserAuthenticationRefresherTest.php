<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Infrastructure;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Exception\ExpiredRefreshTokenException;
use App\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Auth\Domain\Exception\RefreshTokenReuseDetectedException;
use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\AccessToken;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Auth\Domain\ValueObject\IssuedRefreshToken;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationRefresher;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class JwtUserAuthenticationRefresherTest extends TestCase
{
    private const ACCESS_TTL = 900;

    private const REFRESH_TTL = 2_592_000;

    protected function setUp(): void
    {
        parent::setUp();

        DB::shouldReceive('transaction')
            ->andReturnUsing(fn (\Closure $work) => $work());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructor_throws_when_access_ttl_is_not_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->buildRefresher(accessTtlSeconds: 0);
    }

    public function test_constructor_throws_when_refresh_ttl_is_not_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->buildRefresher(refreshTtlSeconds: 0);
    }

    public function test_constructor_throws_when_refresh_ttl_is_not_greater_than_access_ttl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->buildRefresher(accessTtlSeconds: 1000, refreshTtlSeconds: 500);
    }

    public function test_throws_invalid_when_secret_is_malformed(): void
    {
        $this->expectException(InvalidRefreshTokenException::class);

        $this->buildRefresher()->refreshForRestaurant(Uuid::generate(), 'not-a-base64url-secret');
    }

    public function test_throws_invalid_when_token_not_found(): void
    {
        $secret = $this->randomSecret();

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn(null);

        $this->expectException(InvalidRefreshTokenException::class);

        $this->buildRefresher(refreshTokenRepository: $refreshTokenRepository)
            ->refreshForRestaurant(Uuid::generate(), $secret->value());
    }

    public function test_throws_expired_when_token_is_expired(): void
    {
        $secret = $this->randomSecret();
        $expiredToken = $this->buildPersistedToken($secret, expiresAt: '-1 hour');

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($expiredToken);

        $this->expectException(ExpiredRefreshTokenException::class);

        $this->buildRefresher(refreshTokenRepository: $refreshTokenRepository)
            ->refreshForRestaurant(Uuid::generate(), $secret->value());
    }

    public function test_throws_reuse_detected_and_revokes_session_when_token_is_revoked_with_replacement(): void
    {
        $secret = $this->randomSecret();
        $sessionId = Uuid::generate();
        $reusedToken = $this->buildPersistedToken(
            $secret,
            sessionId: $sessionId,
            revokedAt: '-5 minutes',
            replacedById: Uuid::generate(),
        );

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($reusedToken);
        $refreshTokenRepository->shouldReceive('revokeAllInSession')
            ->once()
            ->with(Mockery::on(fn (Uuid $id) => $id->value() === $sessionId->value()));

        $this->expectException(RefreshTokenReuseDetectedException::class);

        $this->buildRefresher(refreshTokenRepository: $refreshTokenRepository)
            ->refreshForRestaurant(Uuid::generate(), $secret->value());
    }

    public function test_throws_invalid_when_token_is_revoked_without_replacement(): void
    {
        $secret = $this->randomSecret();
        $logoutToken = $this->buildPersistedToken(
            $secret,
            revokedAt: '-5 minutes',
            replacedById: null,
        );

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($logoutToken);
        $refreshTokenRepository->shouldNotReceive('revokeAllInSession');

        $this->expectException(InvalidRefreshTokenException::class);

        $this->buildRefresher(refreshTokenRepository: $refreshTokenRepository)
            ->refreshForRestaurant(Uuid::generate(), $secret->value());
    }

    public function test_throws_invalid_when_user_not_found_in_restaurant(): void
    {
        $secret = $this->randomSecret();
        $oldToken = $this->buildPersistedToken($secret);

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($oldToken);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')->once()->andReturn(null);

        $this->expectException(InvalidRefreshTokenException::class);

        $this->buildRefresher(
            refreshTokenRepository: $refreshTokenRepository,
            userRepository: $userRepository,
        )->refreshForRestaurant(Uuid::generate(), $secret->value());
    }

    public function test_rotates_returning_new_pair_preserving_session_and_persisting_in_correct_order(): void
    {
        $secret = $this->randomSecret();
        $sessionId = Uuid::generate();
        $oldToken = $this->buildPersistedToken($secret, sessionId: $sessionId);
        $user = $this->buildUser($oldToken->userId());

        $newSecret = $this->randomSecret();
        $newRefreshEntity = RefreshToken::dddCreate(
            $user->id(),
            $sessionId,
            $newSecret,
            DomainDateTime::create((new \DateTimeImmutable)->modify('+30 days')),
        );
        $newAccessToken = AccessToken::create(
            'new-jwt-value',
            DomainDateTime::create((new \DateTimeImmutable)->modify('+15 minutes')),
        );

        $capturedAccessPayload = null;
        $capturedRefreshIssuerArgs = [];
        $callOrder = [];

        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($oldToken);
        $refreshTokenRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (RefreshToken $token) use ($newRefreshEntity, &$callOrder): bool {
                $callOrder[] = 'create';

                return $token->id()->value() === $newRefreshEntity->id()->value();
            }));
        $refreshTokenRepository->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function (RefreshToken $token) use ($oldToken, $newRefreshEntity, &$callOrder): bool {
                $callOrder[] = 'update';

                return $token->id()->value() === $oldToken->id()->value()
                    && $token->isRevoked()
                    && $token->replacedById()?->value() === $newRefreshEntity->id()->value();
            }));

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')->once()->andReturn($user);

        $accessTokenIssuer = Mockery::mock(AccessTokenIssuerInterface::class);
        $accessTokenIssuer->shouldReceive('issue')
            ->once()
            ->with(Mockery::on(function (AccessTokenPayload $payload) use (&$capturedAccessPayload): bool {
                $capturedAccessPayload = $payload;

                return true;
            }))
            ->andReturn($newAccessToken);

        $refreshTokenIssuer = Mockery::mock(RefreshTokenIssuerInterface::class);
        $refreshTokenIssuer->shouldReceive('issue')
            ->once()
            ->with(
                Mockery::type(Uuid::class),
                Mockery::type(Uuid::class),
                Mockery::type(DomainDateTime::class),
            )
            ->andReturnUsing(function (Uuid $userId, Uuid $rotationSessionId, DomainDateTime $expiresAt) use ($newRefreshEntity, $newSecret, &$capturedRefreshIssuerArgs) {
                $capturedRefreshIssuerArgs = [
                    'userId' => $userId->value(),
                    'sessionId' => $rotationSessionId->value(),
                ];

                return IssuedRefreshToken::create($newRefreshEntity, $newSecret);
            });

        $issuedAuthentication = $this->buildRefresher(
            refreshTokenRepository: $refreshTokenRepository,
            userRepository: $userRepository,
            accessTokenIssuer: $accessTokenIssuer,
            refreshTokenIssuer: $refreshTokenIssuer,
        )->refreshForRestaurant($user->restaurantId(), $secret->value());

        $this->assertSame('new-jwt-value', $issuedAuthentication->accessToken());
        $this->assertSame($newSecret->value(), $issuedAuthentication->refreshToken());

        $this->assertNotNull($capturedAccessPayload);
        $this->assertSame($sessionId->value(), $capturedAccessPayload->sessionId()->value(), 'access token must keep old sessionId');
        $this->assertSame($user->id()->value(), $capturedAccessPayload->userId()->value());
        $this->assertSame($user->restaurantId()->value(), $capturedAccessPayload->restaurantId()->value());
        $this->assertSame($user->role()->value(), $capturedAccessPayload->role()->value());

        $this->assertSame($user->id()->value(), $capturedRefreshIssuerArgs['userId']);
        $this->assertSame($sessionId->value(), $capturedRefreshIssuerArgs['sessionId'], 'new refresh must keep old sessionId');

        $this->assertSame(['create', 'update'], $callOrder, 'create(new) must run before update(old) to satisfy FK');
    }

    private function buildRefresher(
        ?RefreshTokenRepositoryInterface $refreshTokenRepository = null,
        ?UserRepositoryInterface $userRepository = null,
        ?AccessTokenIssuerInterface $accessTokenIssuer = null,
        ?RefreshTokenIssuerInterface $refreshTokenIssuer = null,
        int $accessTtlSeconds = self::ACCESS_TTL,
        int $refreshTtlSeconds = self::REFRESH_TTL,
    ): JwtUserAuthenticationRefresher {
        return new JwtUserAuthenticationRefresher(
            $refreshTokenRepository ?? Mockery::mock(RefreshTokenRepositoryInterface::class),
            $userRepository ?? Mockery::mock(UserRepositoryInterface::class),
            $accessTokenIssuer ?? Mockery::mock(AccessTokenIssuerInterface::class),
            $refreshTokenIssuer ?? Mockery::mock(RefreshTokenIssuerInterface::class),
            $this->fakeTransactionRunner(),
            $accessTtlSeconds,
            $refreshTtlSeconds,
        );
    }

    private function fakeTransactionRunner(): TransactionRunnerInterface
    {
        return new class implements TransactionRunnerInterface
        {
            public function run(callable $callback, int $attempts = 3): mixed
            {
                return $callback();
            }
        };
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
        ?Uuid $replacedById = null,
    ): RefreshToken {
        return RefreshToken::fromPersistence(
            id: Uuid::generate()->value(),
            userId: Uuid::generate()->value(),
            sessionId: ($sessionId ?? Uuid::generate())->value(),
            tokenHash: $secret->hash()->value(),
            expiresAt: (new \DateTimeImmutable)->modify($expiresAt),
            revokedAt: $revokedAt !== null ? (new \DateTimeImmutable)->modify($revokedAt) : null,
            replacedById: $replacedById?->value(),
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
            name: 'Refresher User',
            email: 'refresher@example.com',
            passwordHash: PasswordHash::create('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')->value(),
            pin: null,
            imageSrc: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }
}
