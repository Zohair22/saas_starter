<?php

use App\Http\Controllers\LandingEventController;
use App\Http\Controllers\LandingEventReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/track/landing-event', LandingEventController::class)
    ->middleware('throttle:120,1')
    ->name('landing.track');

Route::get('/track/landing-report', LandingEventReportController::class)
    ->middleware('auth')
    ->name('landing.report');

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');

Route::get('/app', function () {
    return Inertia::render('Dashboard');
})->name('app.dashboard');

Route::get('/app/projects', function () {
    return Inertia::render('Projects/Index');
})->name('app.projects.index');

Route::get('/app/projects/create', function () {
    return Inertia::render('Projects/Create');
})->name('app.projects.create');

Route::get('/app/projects/{id}', function (int $id) {
    return Inertia::render('Projects/Show', ['id' => $id]);
})->name('app.projects.show');

Route::get('/app/projects/{id}/edit', function (int $id) {
    return Inertia::render('Projects/Edit', ['id' => $id]);
})->name('app.projects.edit');

Route::get('/app/projects/{projectId}/tasks', function (int $projectId) {
    return Inertia::render('Tasks/Index', ['projectId' => $projectId]);
})->name('app.tasks.index');

Route::get('/app/projects/{projectId}/tasks/create', function (int $projectId) {
    return Inertia::render('Tasks/Create', ['projectId' => $projectId]);
})->name('app.tasks.create');

Route::get('/app/projects/{projectId}/tasks/{taskId}', function (int $projectId, int $taskId) {
    return Inertia::render('Tasks/Show', ['projectId' => $projectId, 'taskId' => $taskId]);
})->name('app.tasks.show');

Route::get('/app/projects/{projectId}/tasks/{taskId}/edit', function (int $projectId, int $taskId) {
    return Inertia::render('Tasks/Edit', ['projectId' => $projectId, 'taskId' => $taskId]);
})->name('app.tasks.edit');

Route::get('/app/billing', function () {
    return Inertia::render('Billing/Index');
})->name('app.billing.index');

Route::get('/app/memberships', function () {
    return Inertia::render('Memberships/Index');
})->name('app.memberships.index');

Route::get('/app/logs', function () {
    return Inertia::render('Logs/Index');
})->name('app.logs.index');

Route::get('/app/analytics', function () {
    return Inertia::render('Analytics/Index');
})->name('app.analytics.index');

Route::get('/app/settings', function () {
    return Inertia::render('Settings/Profile');
})->name('app.settings.profile');

Route::get('/app/tenant-settings', function () {
    return Inertia::render('Settings/Tenant');
})->name('app.settings.tenant');

Route::get('/app/onboarding', function () {
    return Inertia::render('Onboarding/Index');
})->name('app.onboarding.index');

Route::get('/app/notifications', function () {
    return Inertia::render('Notifications/Index');
})->name('app.notifications.index');

Route::get('/app/admin', function () {
    return Inertia::render('Admin/Index');
})->name('app.admin.index');

Route::get('/app/audit-logs', function () {
    return redirect('/app/logs?tab=audit');
})->name('app.audit-logs.index');

Route::get('/app/activity-logs', function () {
    return redirect('/app/logs?tab=activity');
})->name('app.activity-logs.index');

Route::get('/app/{any}', function () {
    return Inertia::render('Dashboard');
})->where('any', '.*');
