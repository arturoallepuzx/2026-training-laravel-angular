<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserAuthenticationIssuerInterface;
use App\User\Domain\ValueObject\AuthenticationSubject;
use App\User\Domain\ValueObject\IssuedAuthentication;

class JwtUserAuthenticationIssuer implements UserAuthenticationIssuerInterface
{
    public function __construct(
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private RefreshTokenIssuerInterface $refreshTokenIssuer,
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        private int $accessTtlSeconds,
        private int $refreshTtlSeconds,
    ) {
        if ($accessTtlSeconds <= 0) {
            throw new \InvalidArgumentException('Access token TTL must be greater than 0.');
        }

        if ($refreshTtlSeconds <= 0) {
            throw new \InvalidArgumentException('Refresh token TTL must be greater than 0.');
        }

        if ($refreshTtlSeconds <= $accessTtlSeconds) {
            throw new \InvalidArgumentException('Refresh token TTL must be greater than access token TTL.');
        }
    }

    public function issueFor(AuthenticationSubject $subject): IssuedAuthentication
    {
        $now = DomainDateTime::now();
        $sessionId = Uuid::generate();

        $accessExpiresAt = DomainDateTime::create(
            $now->value()->modify('+'.$this->accessTtlSeconds.' seconds')
        );

        $accessToken = $this->accessTokenIssuer->issue(
            AccessTokenPayload::create(
                $subject->userId(),
                $subject->restaurantId(),
                $subject->role(),
                $sessionId,
                $now,
                $accessExpiresAt,
            )
        );

        $refreshExpiresAt = DomainDateTime::create(
            $now->value()->modify('+'.$this->refreshTtlSeconds.' seconds')
        );

        $issuedRefreshToken = $this->refreshTokenIssuer->issue(
            $subject->userId(),
            $sessionId,
            $refreshExpiresAt,
        );

        $this->refreshTokenRepository->create($issuedRefreshToken->entity());

        return IssuedAuthentication::create(
            $accessToken->value(),
            $accessToken->expiresAt(),
            $issuedRefreshToken->secret()->value(),
            $issuedRefreshToken->entity()->expiresAt(),
        );
    }
}
