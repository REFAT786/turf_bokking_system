<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Panel\VerificationController;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\Turf;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'turfs' => Turf::approved()->count(),
            'pending_turfs' => Turf::where('status', 'pending')->count(),
            'owners' => User::where('role', 'owner')->count(),
            'pending_owners' => User::where('role', 'owner')->where('verification_status', User::VERIFICATION_PENDING)->count(),
            'customers' => User::where('role', 'customer')->count(),
            'bookings_month' => Booking::whereIn('status', ['confirmed', 'completed'])->where('starts_at', '>=', now()->startOfMonth())->count(),
            'gmv_month' => Booking::whereIn('status', ['confirmed', 'completed'])->where('starts_at', '>=', now()->startOfMonth())->sum('total_amount'),
            'auto_cancelled_month' => Booking::where('status', 'cancelled')->where('cancel_reason', 'like', 'Auto-cancelled%')->where('cancelled_at', '>=', now()->startOfMonth())->count(),
        ];
        $pendingTurfs = Turf::with('owner')->where('status', 'pending')->latest()->get();
        $topTurfs = Turf::approved()->withCount(['bookings' => fn ($q) => $q->whereIn('status', ['confirmed', 'completed'])->where('starts_at', '>=', now()->startOfMonth())])
            ->orderByDesc('bookings_count')->take(5)->get();

        $pendingOwners = User::where('role', 'owner')->where('verification_status', User::VERIFICATION_PENDING)
            ->oldest('verification_submitted_at')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'pendingTurfs', 'topTurfs', 'pendingOwners'));
    }

    public function turfs(Request $request)
    {
        $turfs = Turf::with('owner')->withCount('bookings')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.turfs', compact('turfs'));
    }

    public function turfStatus(Request $request, Turf $turf)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,approved,suspended']]);
        $turf->update($data);

        return back()->with('success', "{$turf->name} is now {$data['status']}.");
    }

    public function users(Request $request)
    {
        $users = User::with('employer')
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->input('q').'%')
                ->orWhere('phone', 'like', '%'.$request->input('q').'%')
                ->orWhere('email', 'like', '%'.$request->input('q').'%')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function owners(Request $request)
    {
        $status = $request->input('status', User::VERIFICATION_PENDING);
        $owners = User::where('role', 'owner')->withCount('turfs')
            ->when($status !== 'all', fn ($q) => $q->where('verification_status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->input('q').'%')
                ->orWhere('business_name', 'like', '%'.$request->input('q').'%')
                ->orWhere('phone', 'like', '%'.$request->input('q').'%')
                ->orWhere('nid_number', 'like', '%'.$request->input('q').'%')))
            ->orderBy('verification_submitted_at')->paginate(20)->withQueryString();

        return view('admin.owners', compact('owners', 'status'));
    }

    public function ownerDocument(User $user, string $document)
    {
        abort_unless($user->isOwner() && isset(User::VERIFICATION_DOCUMENTS[$document]), 404);
        $path = $user->{User::VERIFICATION_DOCUMENTS[$document][0]};
        abort_unless($path && Storage::disk(VerificationController::DISK)->exists($path), 404);

        // Shown inline in the browser; never cached by proxies.
        return Storage::disk(VerificationController::DISK)->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approveOwner(Request $request, User $user)
    {
        abort_unless($user->isOwner(), 404);
        $user->update([
            'verification_status' => User::VERIFICATION_APPROVED,
            'verification_note' => null,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        return back()->with('success', "{$user->name} is verified. Their approved turfs are now live.");
    }

    public function rejectOwner(Request $request, User $user)
    {
        abort_unless($user->isOwner(), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $user->update([
            'verification_status' => User::VERIFICATION_REJECTED,
            'verification_note' => $data['reason'],
            'verified_at' => null,
            'verified_by' => $request->user()->id,
        ]);

        return back()->with('success', "{$user->name}'s verification was rejected. Their turfs are hidden until they resubmit.");
    }

    /** Support: set a new password for a user who can't reset it themselves. */
    public function setUserPassword(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'Change your own password from My profile.');
        $request->validate(['password' => ['required', \Illuminate\Validation\Rules\Password::min(6)]]);
        $user->forceFill(['password' => $request->input('password'), 'remember_token' => \Illuminate\Support\Str::random(60)])->save();

        return back()->with('success', "New password set for {$user->name}. Share it with them privately and ask them to change it from My profile.");
    }

    public function toggleUser(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422);
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', "{$user->name} ".($user->is_active ? 'enabled' : 'disabled').'.');
    }

    public function bookings(Request $request)
    {
        $bookings = Booking::with('turf')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('code', 'like', '%'.$request->input('q').'%')
                ->orWhere('customer_phone', 'like', '%'.$request->input('q').'%')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.bookings', compact('bookings'));
    }

    public function settings()
    {
        $settings = collect(Setting::DEFAULTS)->mapWithKeys(fn ($v, $k) => [$k => Setting::get($k)]);

        return view('admin.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'payment_deadline_hours' => ['required', 'integer', 'between:1,72'],
            'max_advance_booking_days' => ['required', 'integer', 'between:1,60'],
            'min_hours_before_cancel' => ['required', 'integer', 'between:0,72'],
            'auto_approve_turfs' => ['nullable', 'boolean'],
        ]);
        $data['auto_approve_turfs'] = $request->boolean('auto_approve_turfs') ? '1' : '0';

        foreach ($data as $k => $v) {
            Setting::put($k, (string) $v);
        }

        return back()->with('success', 'Settings saved.');
    }
}
