<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    /** Statuses that occupy a slot. */
    public const BLOCKING = ['pending_payment', 'payment_submitted', 'confirmed', 'completed', 'no_show'];

    public const STATUS_LABELS = [
        'pending_payment' => 'Awaiting payment',
        'payment_submitted' => 'Payment under review',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No-show',
    ];

    public const STATUS_COLORS = [
        'pending_payment' => 'bg-amber-100 text-amber-800',
        'payment_submitted' => 'bg-sky-100 text-sky-800',
        'confirmed' => 'bg-emerald-100 text-emerald-800',
        'completed' => 'bg-slate-200 text-slate-700',
        'cancelled' => 'bg-rose-100 text-rose-700',
        'no_show' => 'bg-zinc-200 text-zinc-700',
    ];

    protected $fillable = [
        'code', 'turf_id', 'user_id', 'customer_name', 'customer_phone', 'starts_at', 'ends_at',
        'total_amount', 'advance_amount', 'paid_amount', 'status', 'source', 'payment_method',
        'payer_number', 'trx_id', 'payment_submitted_at', 'payment_deadline_at', 'confirmed_at',
        'handled_by', 'cancelled_at', 'cancel_reason', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'payment_submitted_at' => 'datetime',
            'payment_deadline_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $b) {
            if (! $b->code) {
                do {
                    $code = 'TB'.strtoupper(Str::random(6));
                } while (static::where('code', $code)->exists());
                $b->code = $code;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function turf(): BelongsTo { return $this->belongsTo(Turf::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function handler(): BelongsTo { return $this->belongsTo(User::class, 'handled_by'); }
    public function review(): HasOne { return $this->hasOne(Review::class); }

    /** Bookings that hold a slot (an unpaid booking past its deadline no longer does). */
    public function scopeBlocking(Builder $q): Builder
    {
        return $q->whereIn('status', self::BLOCKING)
            ->where(fn ($q) => $q->where('status', '!=', 'pending_payment')
                ->orWhereNull('payment_deadline_at')
                ->orWhere('payment_deadline_at', '>', now()));
    }

    /** Cancel every unpaid booking whose payment deadline has passed. Returns count. */
    public static function expireOverdue(): int
    {
        return static::where('status', 'pending_payment')
            ->whereNotNull('payment_deadline_at')
            ->where('payment_deadline_at', '<=', now())
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => 'Auto-cancelled: advance payment not received before the deadline.',
                'updated_at' => now(),
            ]);
    }

    public function statusLabel(): string { return self::STATUS_LABELS[$this->status] ?? $this->status; }
    public function statusColor(): string { return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100'; }
    public function dueAmount(): int { return max(0, $this->total_amount - $this->paid_amount); }
    public function hours(): float { return $this->starts_at->diffInMinutes($this->ends_at) / 60; }
    public function isUpcoming(): bool { return $this->starts_at->isFuture(); }

    public function canBeCancelledByCustomer(): bool
    {
        if (! in_array($this->status, ['pending_payment', 'payment_submitted', 'confirmed'])) {
            return false;
        }
        if ($this->status === 'pending_payment') {
            return true;
        }

        return now()->addHours((int) Setting::get('min_hours_before_cancel'))->lessThan($this->starts_at);
    }
}
