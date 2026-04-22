<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class VerifyController extends Controller
{
    /**
     * Verify a user account via their registration key.
     * Promotes the user from 'unverified' to 'login' role.
     */
    public function verify(string $code): RedirectResponse
    {
        $user = User::where('reg_key', $code)->first();

        if ($user === null) {
            return redirect()->route('login')
                ->withErrors(['code' => 'Ungültiger oder abgelaufener Aktivierungslink.']);
        }

        $user->syncRoles(['login']);
        $user->reg_key = null;
        $user->save();

        return redirect()->route('login')
            ->with('status', 'Dein Konto wurde aktiviert. Du kannst dich jetzt einloggen.');
    }
}
