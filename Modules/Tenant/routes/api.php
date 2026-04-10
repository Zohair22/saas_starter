<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\Api\V1\TenantController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tenants', TenantController::class)->names('api.tenant');
});
