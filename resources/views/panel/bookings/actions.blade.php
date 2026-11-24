<div class="flex flex-wrap gap-1.5" x-data="{ reason: false }">
    @if (in_array($b->status, ['payment_submitted', 'pending_payment']))
        <form method="POST" action="{{ route('panel.bookings.confirm', $b) }}" class="flex gap-1">@csrf
            @if ($b->status === 'pending_payment')
                <input type="number" name="received_amount" value="{{ $b->advance_amount }}" class="input w-24 !py-1 text-xs" title="Amount received">
            @endif
            <button class="btn-primary btn-sm">{{ $b->status === 'payment_submitted' ? '✓ Payment received' : '✓ Confirm' }}</button>
        </form>
    @endif
    @if ($b->status === 'payment_submitted')
        <form method="POST" action="{{ route('panel.bookings.reject', $b) }}">@csrf
            <input type="hidden" name="reason" value="TrxID not found">
            <button class="btn-light btn-sm text-rose-600" onclick="return confirm('Payment not found? This cancels the booking.')">✕ Not received</button>
        </form>
    @endif
    @if ($b->status === 'confirmed')
        <form method="POST" action="{{ route('panel.bookings.complete', $b) }}">@csrf
            <button class="btn-light btn-sm" title="Collected the rest (৳{{ number_format($b->dueAmount()) }})">✓ Played & paid ৳{{ number_format($b->dueAmount()) }}</button>
        </form>
        @if ($b->starts_at->isPast())
            <form method="POST" action="{{ route('panel.bookings.noshow', $b) }}">@csrf
                <button class="btn-light btn-sm">No-show</button>
            </form>
        @endif
    @endif
    @if (in_array($b->status, ['pending_payment', 'payment_submitted', 'confirmed']))
        <form method="POST" action="{{ route('panel.bookings.cancel', $b) }}">@csrf
            <button class="btn-light btn-sm text-slate-500" onclick="return confirm('Cancel booking {{ $b->code }}?')">Cancel</button>
        </form>
    @endif
</div>
