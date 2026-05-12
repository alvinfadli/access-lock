<?php

use AlvinFadli\AccessLock\Support\PasswordManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$prefix = config('access-lock.route_prefix', 'access-lock');
$sessionKey = config('access-lock.session_key', 'access_lock_unlocked');

Route::prefix($prefix)->middleware('web')->group(function () use ($sessionKey) {

    Route::post('/', function (Request $request) use ($sessionKey) {
        $password = (string) ($request->input('password') ?? '');
        $intended = (string) ($request->input('intended') ?? '/');

        if (PasswordManager::verify($password)) {
            $request->session()->put($sessionKey, true);

            return redirect($intended);
        }

        // Wrong password — redirect back to the intended URL.
        // The middleware will intercept it again and show the prompt with the error.
        return redirect($intended)
            ->with('access_lock_error', 'Incorrect password. Please try again.');
    })->name('access-lock.unlock');

});
