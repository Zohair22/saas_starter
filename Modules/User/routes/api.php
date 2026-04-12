<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Api\V1\AdminController;
use Modules\User\Http\Controllers\Api\V1\ApiTokenController;
use Modules\User\Http\Controllers\Api\V1\NotificationController;
use Modules\User\Http\Controllers\Api\V1\ProfileController;
use Modules\User\Http\Controllers\Api\V1\SessionBootstrapController;
use Modules\User\Http\Controllers\Api\V1\UserController;

Route::middleware(['api'])->prefix('v1')->group(function () {
    // Public authentication endpoints
    Route::post('register', [UserController::class, 'store']);
    Route::post('login', [UserController::class, 'login']);

    // Protected user endpoints
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('session/bootstrap', SessionBootstrapController::class)->name('api.session.bootstrap');
        Route::get('me', [UserController::class, 'me']);
        Route::post('logout', [UserController::class, 'logout']);
        Route::get('tokens', [ApiTokenController::class, 'index'])->name('api.token.index');
        Route::post('tokens', [ApiTokenController::class, 'store'])->name('api.token.store');
        Route::delete('tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('api.token.destroy');
        Route::get('users/{user}', [UserController::class, 'show'])->name('api.user.show');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('api.user.destroy');

        // Profile settings
        Route::patch('profile', [ProfileController::class, 'update'])->name('api.profile.update');
        Route::patch('profile/password', [ProfileController::class, 'updatePassword'])->name('api.profile.password');
        Route::delete('profile', [ProfileController::class, 'destroy'])->name('api.profile.destroy');

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('api.notifications.read-all');
        Route::delete('notifications/read', [NotificationController::class, 'clearRead'])->name('api.notifications.clear-read');
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('api.notifications.read');
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('api.notifications.destroy');

        // Super admin
        Route::get('admin/dashboard', [AdminController::class, 'dashboard'])->name('api.admin.dashboard');
        Route::post('admin/impersonate/{user}', [AdminController::class, 'impersonate'])->name('api.admin.impersonate');
    });
});
