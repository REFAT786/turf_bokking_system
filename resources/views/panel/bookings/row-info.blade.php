<p class="font-semibold">{{ $b->customer_name }} <span class="text-xs font-normal text-slate-400">#{{ $b->code }}</span></p>
<p class="text-xs text-slate-500">
    <a href="tel:{{ $b->customer_phone }}" class="hover:text-brand-600">{{ $b->customer_phone }}</a>
    · {{ $b->source === 'walk_in' ? 'Walk-in' : 'Online' }}
</p>
@if ($b->trx_id)
    <p class="mt-1 text-xs"><span class="badge bg-slate-100 font-mono text-slate-700">{{ strtoupper($b->payment_method) }} · {{ $b->trx_id }} · from {{ $b->payer_number }} · ৳{{ number_format($b->paid_amount) }}</span></p>
@endif
@if ($b->status === 'pending_payment' && $b->payment_deadline_at)
    <p class="mt-1 text-xs text-amber-700">Auto-cancels {{ $b->payment_deadline_at->diffForHumans() }}</p>
@endif
@if ($b->notes)<p class="mt-1 text-xs italic text-slate-500">“{{ $b->notes }}”</p>@endif
