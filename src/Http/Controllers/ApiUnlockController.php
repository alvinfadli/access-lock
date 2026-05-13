<?php

namespace AlvinFadli\AccessLock\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ApiUnlockController extends Controller
{
   public function __invoke(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        if (!access_lock_verify($request->password)) {
            return response()->json(['message' => 'Invalid password.'], 401);
        }

        $token = Str::random(64);
        $ttl   = config('access-lock.api.token_ttl', 120);

        Cache::put("access_lock_api:{$token}", true, now()->addMinutes($ttl));

        return response()->json(['token' => $token]);
    }
}