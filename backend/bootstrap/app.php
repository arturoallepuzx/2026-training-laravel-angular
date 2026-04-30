<?php

use App\Auth\Infrastructure\Http\Middleware\AuthenticateAccessToken;
use App\Auth\Infrastructure\Http\Middleware\EnsureTokenMatchesRestaurant;
use App\Auth\Infrastructure\Http\Middleware\RequireRole;
use App\Exceptions\Handler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.access_token' => AuthenticateAccessToken::class,
            'auth.restaurant' => EnsureTokenMatchesRestaurant::class,
            'auth.role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Handler::register($exceptions);
    })->create();
