<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Api\V1\ApiTokenController;
use Modules\User\Http\Controllers\Api\V1\UserController;

Route::middleware(['api'])->prefix('v1')->group(function () {
    // Public authentication endpoints
    Route::post('register', [UserController::class, 'store']);
    Route::post('login', [UserController::class, 'login']);

    // Protected user endpoints
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('me', [UserController::class, 'me']);
        Route::post('logout', [UserController::class, 'logout']);
        Route::get('tokens', [ApiTokenController::class, 'index'])->name('api.token.index');
        Route::post('tokens', [ApiTokenController::class, 'store'])->name('api.token.store');
        Route::delete('tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('api.token.destroy');
        Route::get('users/{user}', [UserController::class, 'show'])->name('api.user.show');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('api.user.destroy');
    });
});
