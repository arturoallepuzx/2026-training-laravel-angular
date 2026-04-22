<?php

declare(strict_types=1);

namespace App\Providers;

use App\Shared\Infrastructure\Persistence\EloquentRestaurantIdResolver;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Infrastructure\Persistence\Repositories\EloquentTaxRepository;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
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
        $this->app->scoped(RestaurantIdResolverInterface::class, EloquentRestaurantIdResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
