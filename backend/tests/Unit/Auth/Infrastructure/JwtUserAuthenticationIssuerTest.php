<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\AccessToken;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Auth\Domain\ValueObject\IssuedRefreshToken;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationIssuer;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\AuthenticationSubject;
use Mockery;
use PHPUnit\Framework\TestCase;

class JwtUserAuthenticationIssuerTest extends TestCase
{
    private const ACCESS_TTL = 900;

    private const REFRESH_TTL = 2_592_000;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_issue_for_emits_tokens_and_persists_refresh_token(): void
    {
        $subject = AuthenticationSubject::create(
            Uuid::generate(),
            Uuid::generate(),
            UserRole::admin(),
        );

        $capturedAccessPayload = null;
        $capturedRefreshSessionId = null;

        $accessToken = AccessToken::create(
            'jwt-value',
            DomainDateTime::create((new \DateTimeImmutable)->modify('+15 minutes')),
        );

        $refreshSecret = RefreshTokenSecret::create(
            rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')
        );
        $refreshEntity = RefreshToken::dddCreate(
            $subject->userId(),
            Uuid::generate(),
            $refreshSecret,
            DomainDateTime::create((new \DateTimeImmutable)->modify('+30 days')),
        );

        $accessTokenIssuer = Mockery::mock(AccessTokenIssuerInterface::class);
        $accessTokenIssuer->shouldReceive('issue')
            ->once()
            ->with(Mockery::on(function (AccessTokenPayload $payload) use (&$capturedAccessPayload) {
                $capturedAccessPayload = $payload;

                return true;
            }))
            ->andReturn($accessToken);

        $refreshTokenIssuer = Mockery::mock(RefreshTokenIssuerInterface::class);
        $refreshTokenIssuer->shouldReceive('issue')
            ->once()
            ->with(
                Mockery::on(fn (Uuid $userId): bool => $userId->value() === $subject->userId()->value()),
                Mockery::on(function (Uuid $sessionId) use (&$capturedRefreshSessionId): bool {
                    $capturedRefreshSessionId = $sessionId->value();

                    return true;
                }),
                Mockery::type(DomainDateTime::class),
            )
            ->andReturn(IssuedRefreshToken::create($refreshEntity, $refreshSecret));

        $refreshRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $refreshRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (RefreshToken $token): bool => $token->id()->value() === $refreshEntity->id()->value()));

        $issuer = new JwtUserAuthenticationIssuer(
            $accessTokenIssuer,
            $refreshTokenIssuer,
            $refreshRepository,
            self::ACCESS_TTL,
            self::REFRESH_TTL,
        );

        $issuedAuthentication = $issuer->issueFor(
            $subject
        );

        $this->assertNotNull($capturedAccessPayload);
        $this->assertSame($subject->userId()->value(), $capturedAccessPayload->userId()->value());
        $this->assertSame($subject->restaurantId()->value(), $capturedAccessPayload->restaurantId()->value());
        $this->assertSame($capturedAccessPayload->sessionId()->value(), $capturedRefreshSessionId);
        $this->assertSame('jwt-value', $issuedAuthentication->accessToken());
        $this->assertSame($refreshSecret->value(), $issuedAuthentication->refreshToken());
    }

    public function test_constructor_throws_when_access_ttl_is_not_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new JwtUserAuthenticationIssuer(
            Mockery::mock(AccessTokenIssuerInterface::class),
            Mockery::mock(RefreshTokenIssuerInterface::class),
            Mockery::mock(RefreshTokenRepositoryInterface::class),
            0,
            self::REFRESH_TTL,
        );
    }

    public function test_constructor_throws_when_refresh_ttl_is_not_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new JwtUserAuthenticationIssuer(
            Mockery::mock(AccessTokenIssuerInterface::class),
            Mockery::mock(RefreshTokenIssuerInterface::class),
            Mockery::mock(RefreshTokenRepositoryInterface::class),
            self::ACCESS_TTL,
            -1,
        );
    }
}
