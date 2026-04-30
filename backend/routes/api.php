<?php

use App\Tax\Infrastructure\Entrypoint\Http\DeleteController as TaxDeleteController;
use App\Tax\Infrastructure\Entrypoint\Http\GetAllController as TaxGetAllController;
use App\Tax\Infrastructure\Entrypoint\Http\GetByIdController as TaxGetByIdController;
use App\Tax\Infrastructure\Entrypoint\Http\PostController as TaxPostController;
use App\Tax\Infrastructure\Entrypoint\Http\PutController as TaxPutController;
use App\User\Infrastructure\Entrypoint\Http\GetMeController as UserGetMeController;
use App\User\Infrastructure\Entrypoint\Http\LoginPostController as UserLoginPostController;
use App\User\Infrastructure\Entrypoint\Http\LogoutPostController as UserLogoutPostController;
use App\User\Infrastructure\Entrypoint\Http\PostController as UserPostController;
use App\User\Infrastructure\Entrypoint\Http\RefreshPostController as UserRefreshPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('/restaurants/{restaurantId}')
    ->whereUuid('restaurantId')
    ->group(function () {
        Route::prefix('/auth')->group(function () {
            Route::post('/login', UserLoginPostController::class);
            Route::post('/refresh', UserRefreshPostController::class);
            Route::post('/logout', UserLogoutPostController::class);
            Route::get('/me', UserGetMeController::class)
                ->middleware('auth.access_token');
        });

        Route::post('/users', UserPostController::class);

        Route::prefix('/taxes')->group(function () {
            Route::get('/', TaxGetAllController::class);
            Route::post('/', TaxPostController::class);
            Route::get('/{taxId}', TaxGetByIdController::class)->whereUuid('taxId');
            Route::put('/{taxId}', TaxPutController::class)->whereUuid('taxId');
            Route::delete('/{taxId}', TaxDeleteController::class)->whereUuid('taxId');
        });
    });
