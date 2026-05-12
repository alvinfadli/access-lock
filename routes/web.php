<?php

use AlvinFadli\AccessLock\Support\PasswordManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$prefix = config('access-lock.route_prefix', 'access-lock');
$sessionKey = config('access-lock.session_key', 'access_lock_unlocked');

Route::prefix($prefix)->group(function () use ($sessionKey) {

    Route::get('/', function () {
        return view('access-lock::unlock');
    })->name('access-lock.show');

    Route::post('/', function (Request $request) use ($sessionKey) {
        $password = $request->input('password', '');

        if (PasswordManager::verify($password)) {
            $request->session()->put($sessionKey, true);

            $intended = $request->session()->pull('access_lock_intended', '/');

            return redirect($intended);
        }

        return redirect()->route('access-lock.show')
            ->with('access_lock_error', 'Incorrect password. Please try again.');
    })->name('access-lock.unlock');

});
