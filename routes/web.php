<?php

use Illuminate\Support\Facades\Route;
use Dev\RateLimitDashboard\Http\Controllers\DashboardController;

Route::group([
    'prefix' => config('rate-limit-dashboard.path', 'rate-limit-dashboard'),
    'middleware' => config('rate-limit-dashboard.middleware', ['web']),
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('rate-limit-dashboard.index');
    Route::post('/clear', [DashboardController::class, 'clear'])->name('rate-limit-dashboard.clear');
    Route::post('/toggle-block', [DashboardController::class, 'toggleBlock'])->name('rate-limit-dashboard.toggle-block');
});