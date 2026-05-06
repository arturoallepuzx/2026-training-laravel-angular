<?php

use App\Tax\Infrastructure\Entrypoint\Http\DeleteController as TaxDeleteController;
use App\Tax\Infrastructure\Entrypoint\Http\GetAllController as TaxGetAllController;
use App\Tax\Infrastructure\Entrypoint\Http\GetByIdController as TaxGetByIdController;
use App\Tax\Infrastructure\Entrypoint\Http\PostController as TaxPostController;
use App\Tax\Infrastructure\Entrypoint\Http\PutController as TaxPutController;
use App\User\Infrastructure\Entrypoint\Http\ForceLogoutPostController as UserForceLogoutPostController;
use App\User\Infrastructure\Entrypoint\Http\GetMeController as UserGetMeController;
use App\User\Infrastructure\Entrypoint\Http\GetUsersWithActiveSessionsController as UserGetActiveSessionsController;
use App\User\Infrastructure\Entrypoint\Http\LoginPostController as UserLoginPostController;
use App\User\Infrastructure\Entrypoint\Http\LogoutAllPostController as UserLogoutAllPostController;
use App\User\Infrastructure\Entrypoint\Http\LogoutPostController as UserLogoutPostController;
use App\User\Infrastructure\Entrypoint\Http\PostController as UserPostController;
use App\User\Infrastructure\Entrypoint\Http\RefreshPostController as UserRefreshPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('/restaurants/{restaurantId}')
    ->whereUuid('restaurantId')
    ->group(function () {
        Route::prefix('/auth')->group(function () {
            Route::post('/login', UserLoginPostController::class)
                ->middleware('throttle:10,1');
            Route::post('/refresh', UserRefreshPostController::class)
                ->middleware('throttle:30,1');
            Route::post('/logout', UserLogoutPostController::class)
                ->middleware('throttle:30,1');
            Route::post('/logout-all', UserLogoutAllPostController::class)
                ->middleware(['auth.access_token', 'auth.restaurant', 'throttle:30,1']);
            Route::get('/me', UserGetMeController::class)
                ->middleware('auth.access_token');
        });

        Route::post('/users', UserPostController::class)
            ->middleware(['auth.access_token', 'auth.restaurant', 'auth.role:admin']);

        Route::get('/users/active-sessions', UserGetActiveSessionsController::class)
            ->middleware(['auth.access_token', 'auth.restaurant', 'auth.role:admin']);

        Route::post('/users/{userId}/force-logout', UserForceLogoutPostController::class)
            ->whereUuid('userId')
            ->middleware(['auth.access_token', 'auth.restaurant', 'auth.role:admin', 'throttle:30,1']);

        Route::prefix('/taxes')
            ->middleware(['auth.access_token', 'auth.restaurant'])
            ->group(function () {
                Route::get('/', TaxGetAllController::class);
                Route::get('/{taxId}', TaxGetByIdController::class)->whereUuid('taxId');

                Route::post('/', TaxPostController::class)->middleware('auth.role:admin');

                Route::put('/{taxId}', TaxPutController::class)->whereUuid('taxId')->middleware('auth.role:admin');

                Route::delete('/{taxId}', TaxDeleteController::class)->whereUuid('taxId')->middleware('auth.role:admin');
            });
    });
