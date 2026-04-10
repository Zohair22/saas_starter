<?php

use Illuminate\Support\Facades\Route;
use Modules\Task\Http\Controllers\TaskController;

Route::scopeBindings()->middleware(['auth:sanctum', 'tenant', 'tenant.member', 'throttle:api', 'tenant.api.rate'])->prefix('v1')->group(function () {
    Route::get('projects/{project}/tasks', [TaskController::class, 'index'])->name('task.index');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('task.store');
    Route::get('projects/{project}/tasks/{task}', [TaskController::class, 'show'])->name('task.show');
    Route::match(['put', 'patch'], 'projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('task.update');
    Route::delete('projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('task.destroy');
});
