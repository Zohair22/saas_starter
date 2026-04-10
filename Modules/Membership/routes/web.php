<?php

use Illuminate\Support\Facades\Route;
use Modules\Membership\Http\Controllers\MembershipController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('memberships', MembershipController::class)->names('membership');
});
