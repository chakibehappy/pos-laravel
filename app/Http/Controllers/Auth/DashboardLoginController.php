<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

use App\Models\User;
use App\Models\PosUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Helpers\ActivityLogger;

class DashboardLoginController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/Login');
    }

    
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'No account found with this email.',
            ]);
        }

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Incorrect password.',
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $deviceName = $request->header('User-Agent') ?? 'Unknown Device';
        $token = $user->createToken($deviceName)->plainTextToken;

        ActivityLogger::log('login', '', NULL, 'Login Admin Dashboard', $user->posUser->id);

        // return plain token to frontend
        return redirect()->intended('/dashboard')->with([
            'api_token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            // Since we are in a 'web' session, currentAccessToken() is null.
            // We need to find the token that matches the current device/session.
            $deviceName = $request->header('User-Agent') ?? 'Unknown Device';
            // Option A: Delete the token for THIS device only
            $user->tokens()->where('name', $deviceName)->delete();
            // Option B (Safer for now): Delete ALL tokens for this user
            // $user->tokens()->delete();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        ActivityLogger::log('logout', '', NULL, 'Logout dari Admin Dashboard', $user->posUser->id);

        return redirect('/login');
    }
}
