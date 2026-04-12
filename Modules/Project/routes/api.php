<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\Http\Controllers\ProjectController;

Route::middleware(['auth:sanctum', 'tenant', 'tenant.member', 'tenant.lifecycle', 'throttle:api', 'tenant.api.rate'])->prefix('v1')->group(function () {
    Route::get('projects', [ProjectController::class, 'index'])->name('project.index');
    Route::post('projects', [ProjectController::class, 'store'])
        ->middleware('feature.limit:max_projects')
        ->name('project.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('project.show');
    Route::match(['put', 'patch'], 'projects/{project}', [ProjectController::class, 'update'])->name('project.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('project.destroy');
});
