<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Panel\VerificationController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [$field => $field === 'phone' ? $this->normalizePhone($data['login']) : mb_strtolower(trim($data['login'])), 'password' => $data['password']];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Wrong phone/email or password.'])->onlyInput('login');
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()->withErrors(['login' => 'Your account is disabled. Please contact support.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->homeRoute());
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', ['asOwner' => $request->routeIs('register.owner')]);
    }

    public function register(Request $request)
    {
        $asOwner = $request->routeIs('register.owner.store');
        self::normalizeContact($request);

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ];
        $rules += User::contactRules();
        $messages = User::contactMessages();
        if ($asOwner) {
            $rules += ['business_name' => ['required', 'string', 'max:150']] + VerificationController::rules();
            $messages += VerificationController::messages();
        }
        $data = $request->validate($rules, $messages);

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => $data['password'],
            'business_name' => $asOwner ? $data['business_name'] : null,
            'role' => $asOwner ? User::ROLE_OWNER : User::ROLE_CUSTOMER,
        ]);

        if ($asOwner) {
            VerificationController::submit($request, $user);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($asOwner) {
            return redirect()->route('panel.turfs.create')->with('success', 'Welcome! Your documents are under review — meanwhile, set up your first turf. It goes live as soon as your account is verified.');
        }

        return redirect()->intended(route('turfs.index'))->with('success', 'Account created. Find a turf and book your game!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** Normalise contact fields on the request before validating (so uniqueness checks compare like with like). */
    public static function normalizeContact(Request $request): void
    {
        $request->merge([
            'phone' => self::normalizePhone((string) $request->input('phone')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }
}
