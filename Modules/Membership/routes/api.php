<?php

use Illuminate\Support\Facades\Route;
use Modules\Membership\Http\Controllers\InvitationController;
use Modules\Membership\Http\Controllers\MembershipController;

Route::prefix('v1')->group(function () {
    Route::get('invitations/{token}', [InvitationController::class, 'showByToken'])->name('invitation.show-by-token');
    Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitation.accept');
});

Route::middleware(['auth:sanctum', 'tenant', 'tenant.member', 'throttle:api', 'tenant.api.rate'])->prefix('v1')->group(function () {
    Route::get('invitations', [InvitationController::class, 'index'])->name('invitation.index');
    Route::get('memberships', [MembershipController::class, 'index'])->name('membership.index');
    Route::post('memberships', [MembershipController::class, 'store'])
        ->middleware('feature.limit:max_users')
        ->name('membership.store');
    Route::get('memberships/{membership}', [MembershipController::class, 'show'])->name('membership.show');
    Route::match(['put', 'patch'], 'memberships/{membership}', [MembershipController::class, 'update'])->name('membership.update');
    Route::delete('memberships/{membership}', [MembershipController::class, 'destroy'])->name('membership.destroy');

    Route::post('invitations', [InvitationController::class, 'store'])->name('invitation.store');
    Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitation.destroy');
});
