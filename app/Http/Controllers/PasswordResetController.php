<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/** "Forgot password?" — email a reset link, then let the user choose a new password. */
class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request)
    {
        $request->validate(['login' => ['required', 'string', 'max:150']]);
        $login = trim($request->input('login'));

        // Accept email or mobile number; find the account's email either way.
        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? User::where('email', mb_strtolower($login))->first()
            : User::where('phone', AuthController::normalizePhone($login))->first();

        if ($user && $user->is_active) {
            Password::sendResetLink(['email' => $user->email]);
        }

        // Same answer whether or not the account exists, so nobody can probe which numbers/emails are registered.
        return back()->with('status', 'If an account matches, we have emailed a password reset link to its email address. It is valid for 60 minutes.');
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(6)],
        ]);

        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Your password has been changed. Log in with your new password.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired. Please request a new one.']);
    }
}
