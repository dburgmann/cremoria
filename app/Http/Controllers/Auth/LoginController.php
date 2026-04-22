<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        // Attempt login by username
        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $remember)) {
            $user = Auth::user();

            // Only allow verified (login-role) users
            if (!$user->hasRole('login')) {
                Auth::logout();
                return back()->withErrors([
                    'username' => 'Dein Konto ist noch nicht aktiviert.',
                ])->onlyInput('username');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('game.overview'));
        }

        return back()->withErrors([
            'username' => 'Benutzername oder Passwort ist falsch.',
        ])->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
