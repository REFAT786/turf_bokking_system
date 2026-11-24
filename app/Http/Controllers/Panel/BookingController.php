<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Turf;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $turfs = $request->user()->managedTurfs()->get();
        $q = Booking::with('turf')->whereIn('turf_id', $turfs->pluck('id'));

        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }
        if ($request->filled('turf')) {
            $q->where('turf_id', $request->integer('turf'));
        }
        if ($d = $this->dateInput($request, 'date')) {
            $q->whereBetween('starts_at', [$d->copy()->startOfDay(), $d->copy()->endOfDay()]);
        }
        if ($s = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('code', 'like', "%{$s}%")
                ->orWhere('customer_phone', 'like', "%{$s}%")
                ->orWhere('customer_name', 'like', "%{$s}%")
                ->orWhere('trx_id', 'like', "%{$s}%"));
        }

        $bookings = $q->orderByDesc('starts_at')->paginate(20)->withQueryString();

        return view('panel.bookings.index', compact('bookings', 'turfs'));
    }

    /** Day schedule grid for one turf, with walk-in booking form. */
    public function schedule(Request $request)
    {
        $turfs = $request->user()->managedTurfs()->get();
        if ($turfs->isEmpty()) {
            return redirect()->route('panel.dashboard');
        }

        $turf = $turfs->firstWhere('id', $request->integer('turf')) ?? $turfs->first();
        $date = $this->dateInput($request, 'date', today())->startOfDay();
        $slots = $turf->slotsFor($date);

        $open = $date->copy()->setTimeFromTimeString($turf->opens_at);
        $bookings = $turf->bookings()->blocking()
            ->where('starts_at', '<', $open->copy()->addDay())
            ->where('ends_at', '>', $open)
            ->orderBy('starts_at')->get();

        // map slot start => booking
        foreach ($slots as &$slot) {
            $slot['booking'] = $bookings->first(fn ($b) => $b->starts_at->lessThan($slot['end']) && $b->ends_at->greaterThan($slot['start']));
        }

        return view('panel.schedule', compact('turfs', 'turf', 'date', 'slots'));
    }

    public function storeWalkIn(Request $request, BookingService $service)
    {
        $turf = Turf::findOrFail($request->integer('turf_id'));
        abort_unless($request->user()->canManageTurf($turf), 403);

        $request->merge(['phone' => AuthController::normalizePhone((string) $request->input('phone'))]);
        $data = $request->validate([
            'slots' => ['required', 'array', 'min:1'],
            'slots.*' => ['required', 'date'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = $service->create($turf, $data['slots'], $data, null, 'walk_in', $request->user());

        return back()->with('success', "Booking {$booking->code} added for {$booking->customer_name}.");
    }

    public function confirm(Request $request, Booking $booking)
    {
        $this->guard($request, $booking);
        abort_unless(in_array($booking->status, ['payment_submitted', 'pending_payment']), 422);

        $data = $request->validate(['received_amount' => ['nullable', 'integer', 'min:0']]);

        $booking->update([
            'status' => 'confirmed',
            'paid_amount' => $data['received_amount'] ?? $booking->paid_amount,
            'confirmed_at' => now(),
            'handled_by' => $request->user()->id,
        ]);

        return back()->with('success', "Booking {$booking->code} confirmed.");
    }

    public function rejectPayment(Request $request, Booking $booking)
    {
        $this->guard($request, $booking);
        abort_unless($booking->status === 'payment_submitted', 422);

        $booking->update([
            'status' => 'cancelled',
            'paid_amount' => 0,
            'cancelled_at' => now(),
            'handled_by' => $request->user()->id,
            'cancel_reason' => 'Payment could not be verified'.($request->filled('reason') ? ': '.$request->input('reason') : '.'),
        ]);

        return back()->with('success', "Booking {$booking->code} cancelled — payment not verified.");
    }

    public function complete(Request $request, Booking $booking)
    {
        $this->guard($request, $booking);
        abort_unless($booking->status === 'confirmed', 422);

        $booking->update([
            'status' => 'completed',
            'paid_amount' => $booking->total_amount,
            'handled_by' => $request->user()->id,
        ]);

        return back()->with('success', "Booking {$booking->code} marked as played & fully paid.");
    }

    public function noShow(Request $request, Booking $booking)
    {
        $this->guard($request, $booking);
        abort_unless($booking->status === 'confirmed', 422);
        $booking->update(['status' => 'no_show', 'handled_by' => $request->user()->id]);

        return back()->with('success', "Booking {$booking->code} marked as no-show (advance kept).");
    }

    public function cancel(Request $request, Booking $booking)
    {
        $this->guard($request, $booking);
        abort_unless(in_array($booking->status, ['pending_payment', 'payment_submitted', 'confirmed']), 422);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'handled_by' => $request->user()->id,
            'cancel_reason' => 'Cancelled by turf'.($request->filled('reason') ? ': '.$request->input('reason') : ''),
        ]);

        return back()->with('success', "Booking {$booking->code} cancelled.");
    }

    private function guard(Request $request, Booking $booking): void
    {
        abort_unless($request->user()->canManageTurf($booking->turf), 403);
    }
}
