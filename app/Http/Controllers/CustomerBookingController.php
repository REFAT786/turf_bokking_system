<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Turf;
use App\Services\BookingService;
use Illuminate\Http\Request;

class CustomerBookingController extends Controller
{
    public function store(Request $request, Turf $turf, BookingService $service)
    {
        abort_unless($turf->isLive(), 404);

        $request->merge(['phone' => AuthController::normalizePhone((string) $request->input('phone', $request->user()->phone))]);
        $data = $request->validate([
            'slots' => ['required', 'array', 'min:1'],
            'slots.*' => ['required', 'date'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Prevent one user holding lots of unpaid slots.
        $openHolds = $request->user()->bookings()->where('status', 'pending_payment')->blocking()->count();
        if ($openHolds >= 2) {
            return back()->withErrors(['slots' => 'You already have 2 unpaid bookings. Pay or cancel them before booking more.']);
        }

        $booking = $service->create($turf, $data['slots'], $data, $request->user());

        return redirect()->route('my.bookings.show', $booking)
            ->with('success', 'Slot reserved! Pay the advance before the deadline to confirm it.');
    }

    public function index(Request $request)
    {
        $bookings = $request->user()->bookings()->with('turf', 'review')
            // Upcoming first (soonest first), then past (most recent first). App time, not DB-server time.
            ->orderByRaw('CASE WHEN starts_at >= ? THEN 0 ELSE 1 END', [now()])
            ->orderByRaw('CASE WHEN starts_at >= ? THEN starts_at END ASC', [now()])
            ->orderByDesc('starts_at')
            ->paginate(15);

        return view('my.bookings', compact('bookings'));
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorizeOwn($request, $booking);
        $booking->load('turf', 'review');

        return view('my.booking', compact('booking'));
    }

    public function submitPayment(Request $request, Booking $booking)
    {
        $this->authorizeOwn($request, $booking);
        $booking->refresh();

        if ($booking->status !== 'pending_payment' || $booking->payment_deadline_at?->isPast()) {
            return back()->withErrors(['trx_id' => 'This booking can no longer accept payment.']);
        }

        $methods = array_keys($booking->turf->paymentAccounts());
        $data = $request->validate([
            'payment_method' => ['required', 'in:'.implode(',', $methods)],
            'payer_number' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'trx_id' => ['required', 'string', 'min:6', 'max:30', 'alpha_num'],
            'amount' => ['required', 'integer', 'min:'.$booking->advance_amount],
        ], ['payer_number.regex' => 'Enter the mobile number you sent money from.']);

        $dupe = Booking::where('trx_id', strtoupper($data['trx_id']))->where('id', '!=', $booking->id)->exists();
        if ($dupe) {
            return back()->withErrors(['trx_id' => 'This transaction ID has already been used.']);
        }

        $booking->update([
            'status' => 'payment_submitted',
            'payment_method' => $data['payment_method'],
            'payer_number' => $data['payer_number'],
            'trx_id' => strtoupper($data['trx_id']),
            'paid_amount' => $data['amount'],
            'payment_submitted_at' => now(),
        ]);

        return back()->with('success', 'Payment info sent. The turf will verify it shortly and confirm your booking.');
    }

    public function cancel(Request $request, Booking $booking)
    {
        $this->authorizeOwn($request, $booking);
        if (! $booking->canBeCancelledByCustomer()) {
            return back()->withErrors(['cancel' => 'This booking can no longer be cancelled online. Please contact the turf.']);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => 'Cancelled by customer'.($request->filled('reason') ? ': '.$request->input('reason') : ''),
        ]);

        $msg = $booking->paid_amount > 0
            ? 'Booking cancelled. Contact the turf about your advance refund.'
            : 'Booking cancelled.';

        return back()->with('success', $msg);
    }

    public function review(Request $request, Booking $booking)
    {
        $this->authorizeOwn($request, $booking);
        abort_unless($booking->status === 'completed' || ($booking->status === 'confirmed' && $booking->ends_at->isPast()), 403);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::updateOrCreate(
            ['booking_id' => $booking->id],
            $data + ['turf_id' => $booking->turf_id, 'user_id' => $request->user()->id],
        );

        return back()->with('success', 'Thanks for your review!');
    }

    private function authorizeOwn(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 404);
    }
}
