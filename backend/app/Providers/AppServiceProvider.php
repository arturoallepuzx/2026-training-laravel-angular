<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\Interfaces\AccessTokenVerifierInterface;
use App\Auth\Domain\Interfaces\RefreshTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Infrastructure\Persistence\Repositories\EloquentRefreshTokenRepository;
use App\Auth\Infrastructure\Services\FirebaseJwtAccessTokenIssuer;
use App\Auth\Infrastructure\Services\FirebaseJwtAccessTokenVerifier;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationGlobalRevoker;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationIssuer;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationRefresher;
use App\Auth\Infrastructure\Services\JwtUserAuthenticationRevoker;
use App\Auth\Infrastructure\Services\RandomRefreshTokenIssuer;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Shared\Infrastructure\Persistence\EloquentRestaurantIdResolver;
use App\Shared\Infrastructure\Persistence\EloquentUserIdResolver;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Infrastructure\Persistence\Repositories\EloquentTaxRepository;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserActiveSessionsFinderInterface;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserAuthenticationIssuerInterface;
use App\User\Domain\Interfaces\UserAuthenticationRefresherInterface;
use App\User\Domain\Interfaces\UserAuthenticationRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Repositories\EloquentUserActiveSessionsFinder;
use App\User\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\User\Infrastructure\Services\LaravelPasswordHasher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(PasswordHasherInterface::class, LaravelPasswordHasher::class);
        $this->app->bind(TaxRepositoryInterface::class, EloquentTaxRepository::class);
        $this->app->bind(RefreshTokenIssuerInterface::class, RandomRefreshTokenIssuer::class);
        $this->app->bind(RefreshTokenRepositoryInterface::class, EloquentRefreshTokenRepository::class);
        $this->app->bind(
            UserActiveSessionsFinderInterface::class,
            EloquentUserActiveSessionsFinder::class,
        );

        $this->app->scoped(AccessTokenIssuerInterface::class, function (): AccessTokenIssuerInterface {
            return new FirebaseJwtAccessTokenIssuer($this->jwtSecret());
        });

        $this->app->scoped(AccessTokenVerifierInterface::class, function (): AccessTokenVerifierInterface {
            return new FirebaseJwtAccessTokenVerifier($this->jwtSecret());
        });

        $this->app->scoped(UserAuthenticationIssuerInterface::class, function ($app): UserAuthenticationIssuerInterface {
            return new JwtUserAuthenticationIssuer(
                $app->make(AccessTokenIssuerInterface::class),
                $app->make(RefreshTokenIssuerInterface::class),
                $app->make(RefreshTokenRepositoryInterface::class),
                $this->accessTtlSeconds(),
                $this->refreshTtlSeconds(),
            );
        });

        $this->app->scoped(UserAuthenticationRefresherInterface::class, function ($app): UserAuthenticationRefresherInterface {
            return new JwtUserAuthenticationRefresher(
                $app->make(RefreshTokenRepositoryInterface::class),
                $app->make(UserRepositoryInterface::class),
                $app->make(AccessTokenIssuerInterface::class),
                $app->make(RefreshTokenIssuerInterface::class),
                $this->accessTtlSeconds(),
                $this->refreshTtlSeconds(),
            );
        });

        $this->app->scoped(UserAuthenticationRevokerInterface::class, function ($app): UserAuthenticationRevokerInterface {
            return new JwtUserAuthenticationRevoker(
                $app->make(RefreshTokenRepositoryInterface::class),
                $app->make(UserRepositoryInterface::class),
            );
        });

        $this->app->scoped(
            UserAuthenticationGlobalRevokerInterface::class,
            JwtUserAuthenticationGlobalRevoker::class,
        );

        $this->app->scoped(RestaurantIdResolverInterface::class, EloquentRestaurantIdResolver::class);
        $this->app->scoped(UserIdResolverInterface::class, EloquentUserIdResolver::class);
        $this->app->scoped(AuthContextHolder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    private function jwtSecret(): string
    {
        $secret = (string) config('auth_tokens.jwt_secret');

        if (trim($secret) === '') {
            throw new \InvalidArgumentException('AUTH_JWT_SECRET is required.');
        }

        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('AUTH_JWT_SECRET is invalid.');
        }

        return $secret;
    }

    private function accessTtlSeconds(): int
    {
        $seconds = (int) config('auth_tokens.access_ttl_seconds');

        if ($seconds <= 0) {
            throw new \InvalidArgumentException('AUTH_ACCESS_TTL_SECONDS must be greater than 0.');
        }

        return $seconds;
    }

    private function refreshTtlSeconds(): int
    {
        $accessTtlSeconds = $this->accessTtlSeconds();
        $seconds = (int) config('auth_tokens.refresh_ttl_seconds');

        if ($seconds <= 0) {
            throw new \InvalidArgumentException('AUTH_REFRESH_TTL_SECONDS must be greater than 0.');
        }

        if ($seconds <= $accessTtlSeconds) {
            throw new \InvalidArgumentException('AUTH_REFRESH_TTL_SECONDS must be greater than AUTH_ACCESS_TTL_SECONDS.');
        }

        return $seconds;
    }
}
