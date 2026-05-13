<?php

use AlvinFadli\AccessLock\Http\Controllers\ApiUnlockController;
use Illuminate\Support\Facades\Route;

Route::post(
    config('access-lock.route_prefix', 'access-lock') . '/api/unlock',
    ApiUnlockController::class
)->name('access-lock.api.unlock');