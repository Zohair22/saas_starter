<?php

use Illuminate\Support\Facades\Route;
use Modules\ActivityLog\Http\Controllers\ActivityLogController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('activitylogs', ActivityLogController::class)->names('activitylog');
});
