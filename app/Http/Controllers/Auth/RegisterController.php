<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\Game\ExistenceCreatorService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Handles user registration.
 * - No captcha (removed per plan).
 * - Calls ExistenceCreatorService after user creation (bug fix: was missing in old register.php).
 * - Sends email verification link.
 */
class RegisterController extends Controller
{
    public function __construct(private ExistenceCreatorService $existenceCreator)
    {
    }

    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $regKey = Str::random(64);

        $user = User::create([
            'username' => $request->validated('username'),
            'email'    => $request->validated('email'),
            'password' => $request->validated('password'),
            'reg_key'  => $regKey,
        ]);

        // Assign unverified role (replaces Kohana auth add(role, 'unverified'))
        $user->assignRole('unverified');

        // Create starting town + resources (bug fix: was never called in old Kohana register.php)
        $this->existenceCreator->createNewExistence($user);

        // Send verification email
        $this->sendVerificationEmail($user, $regKey);

        return redirect()->route('login')
            ->with('status', 'Dein Konto wurde erstellt. Bitte überprüfe deine E-Mail zur Aktivierung.');
    }

    private function sendVerificationEmail(User $user, string $regKey): void
    {
        $verifyUrl = route('verify', ['code' => $regKey]);

        \Illuminate\Support\Facades\Mail::raw(
            "Bitte aktiviere dein Cremoria-Konto unter: {$verifyUrl}",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Cremoria – Konto aktivieren');
            }
        );
    }
}
