<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\BillingController;
use Modules\Billing\Http\Controllers\StripeWebhookController;
use Modules\Billing\Http\Controllers\UsageDashboardController;

Route::prefix('v1')->group(function () {
    Route::post('billing/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('billing.webhook');

    Route::middleware(['auth:sanctum', 'tenant', 'tenant.member', 'throttle:api', 'tenant.api.rate'])->group(function () {
        Route::get('billing/plans', [BillingController::class, 'index'])->name('billing.plans');
        Route::get('billing/usage', [UsageDashboardController::class, 'show'])->name('billing.usage');
        Route::post('billing/subscribe', [BillingController::class, 'store'])->name('billing.subscribe');
        Route::patch('billing/subscription', [BillingController::class, 'update'])->name('billing.swap');
        Route::delete('billing/subscription', [BillingController::class, 'destroy'])->name('billing.cancel');
    });
});
