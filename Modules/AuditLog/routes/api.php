<?php

use Illuminate\Support\Facades\Route;
use Modules\AuditLog\Http\Controllers\AuditLogController;

Route::middleware(['auth:sanctum', 'tenant', 'tenant.member', 'throttle:api', 'tenant.api.rate'])->prefix('v1')->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-log.index');
});
