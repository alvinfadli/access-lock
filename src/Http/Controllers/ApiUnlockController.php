<?php

namespace AlvinFadli\AccessLock\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApiUnlockController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        if (!access_lock_verify($request->password)) {
            return response()->json(['message' => 'Invalid password.'], 401);
        }

        return response()->json(['token' => $request->password]);
    }
}