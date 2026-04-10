<?php

use App\Tax\Infrastructure\Entrypoint\Http\DeleteController as TaxDeleteController;
use App\Tax\Infrastructure\Entrypoint\Http\GetAllController as TaxGetAllController;
use App\Tax\Infrastructure\Entrypoint\Http\GetByIdController as TaxGetByIdController;
use App\Tax\Infrastructure\Entrypoint\Http\PostController as TaxPostController;
use App\Tax\Infrastructure\Entrypoint\Http\PutController as TaxPutController;
use App\User\Infrastructure\Entrypoint\Http\PostController as UserPostController;
use Illuminate\Support\Facades\Route;

Route::post('/users', UserPostController::class);

Route::prefix('/restaurants/{restaurantId}/taxes')->group(function () {
    Route::get('/', TaxGetAllController::class);
    Route::post('/', TaxPostController::class);
    Route::get('/{taxId}', TaxGetByIdController::class);
    Route::put('/{taxId}', TaxPutController::class);
    Route::delete('/{taxId}', TaxDeleteController::class);
});
