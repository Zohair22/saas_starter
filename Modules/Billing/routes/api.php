<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\BillingController;
use Modules\Billing\Http\Controllers\InvoiceController;
use Modules\Billing\Http\Controllers\PaymentMethodController;
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

        // Invoices
        Route::get('billing/invoices', [InvoiceController::class, 'index'])->name('billing.invoices');

        // Payment methods
        Route::get('billing/payment-methods', [PaymentMethodController::class, 'index'])->name('billing.payment-methods.index');
        Route::post('billing/payment-methods', [PaymentMethodController::class, 'store'])->name('billing.payment-methods.store');
        Route::patch('billing/payment-methods/default', [PaymentMethodController::class, 'setDefault'])->name('billing.payment-methods.default');
        Route::delete('billing/payment-methods/{paymentMethodId}', [PaymentMethodController::class, 'destroy'])->name('billing.payment-methods.destroy');
    });
});
