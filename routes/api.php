<?php

use AlvinFadli\AccessLock\Http\Controllers\ApiUnlockController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::post(
        config('access-lock.route_prefix', 'access-lock') . '/unlock',
        ApiUnlockController::class
    )->name('access-lock.api.unlock');
});