<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

use App\Auth\Domain\Exception\ExpiredRefreshTokenException;
use App\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Auth\Domain\Exception\RefreshTokenReuseDetectedException;
use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserAuthenticationRefresherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\IssuedAuthentication;
use Illuminate\Support\Facades\DB;

class JwtUserAuthenticationRefresher implements UserAuthenticationRefresherInterface
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        private UserRepositoryInterface $userRepository,
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private RefreshTokenIssuerInterface $refreshTokenIssuer,
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

    public function refreshForRestaurant(Uuid $restaurantId, string $refreshCredential): IssuedAuthentication
    {
        try {
            $secret = RefreshTokenSecret::create($refreshCredential);
        } catch (\InvalidArgumentException $e) {
            throw InvalidRefreshTokenException::notFound();
        }

        $oldToken = $this->refreshTokenRepository->findByTokenHash($secret->hash());

        if ($oldToken === null) {
            throw InvalidRefreshTokenException::notFound();
        }

        if ($oldToken->isExpired()) {
            throw ExpiredRefreshTokenException::expiredAt($oldToken->expiresAt());
        }

        if ($oldToken->isRevoked() && $oldToken->replacedById() !== null) {
            $this->refreshTokenRepository->revokeAllInSession($oldToken->sessionId());

            throw RefreshTokenReuseDetectedException::forSession($oldToken->sessionId());
        }

        if ($oldToken->isRevoked()) {
            throw InvalidRefreshTokenException::notFound();
        }

        $user = $this->userRepository->findById($oldToken->userId(), $restaurantId);

        if ($user === null) {
            throw InvalidRefreshTokenException::notFound();
        }

        $now = DomainDateTime::now();
        $sessionId = $oldToken->sessionId();

        $newAccessToken = $this->accessTokenIssuer->issue(
            AccessTokenPayload::create(
                $user->id(),
                $user->restaurantId(),
                $user->role(),
                $sessionId,
                $now,
                DomainDateTime::create($now->value()->modify('+'.$this->accessTtlSeconds.' seconds')),
            )
        );

        $newIssuedRefresh = $this->refreshTokenIssuer->issue(
            $user->id(),
            $sessionId,
            DomainDateTime::create($now->value()->modify('+'.$this->refreshTtlSeconds.' seconds')),
        );

        DB::transaction(function () use ($oldToken, $newIssuedRefresh): void {
            $this->refreshTokenRepository->create($newIssuedRefresh->entity());
            $oldToken->markReplacedBy($newIssuedRefresh->entity()->id());
            $this->refreshTokenRepository->update($oldToken);
        }, 3);

        return IssuedAuthentication::create(
            $newAccessToken->value(),
            $newAccessToken->expiresAt(),
            $newIssuedRefresh->secret()->value(),
            $newIssuedRefresh->entity()->expiresAt(),
        );
    }
}
