<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** "My profile" for every logged-in user: see account details, edit them, change password. */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $stats = null;
        if ($user->isCustomer()) {
            $b = $user->bookings();
            $stats = [
                'upcoming' => (clone $b)->whereIn('status', ['pending_payment', 'payment_submitted', 'confirmed'])->where('ends_at', '>', now())->count(),
                'played' => (clone $b)->where('status', 'completed')->count(),
                'turfs' => (clone $b)->whereIn('status', ['confirmed', 'completed'])->distinct()->count('turf_id'),
            ];
        }

        return view('profile', compact('user', 'stats'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        AuthController::normalizeContact($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            ...\App\Models\User::contactRules($user->id),
            'business_name' => [$user->isOwner() ? 'required' : 'prohibited', 'nullable', 'string', 'max:150'],
        ], \App\Models\User::contactMessages());

        $user->update(array_filter($data, fn ($v, $k) => $k !== 'business_name' || $user->isOwner(), ARRAY_FILTER_USE_BOTH));

        return back()->with('success', 'Your details were saved.');
    }

    public function password(Request $request)
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'password.different' => 'Choose a password different from your current one.',
        ]);

        $request->user()->update(['password' => $request->input('password')]); // hashed by the model cast
        $request->session()->regenerate();

        return back()->with('success', 'Password changed. Use the new password next time you log in.');
    }
}
