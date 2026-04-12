<?php

use Illuminate\Support\Facades\Route;
use Modules\ActivityLog\Http\Controllers\ActivityLogController;

Route::middleware(['auth:sanctum', 'tenant', 'tenant.member', 'tenant.lifecycle', 'throttle:api', 'tenant.api.rate'])->prefix('v1')->group(function () {
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-log.index');
});
