<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Defaults used until the super admin changes them. */
    public const DEFAULTS = [
        'payment_deadline_hours' => '4',
        'max_advance_booking_days' => '14',
        'min_hours_before_cancel' => '6',
        'support_phone' => '01700000000',
        'auto_approve_turfs' => '0',
    ];

    public static function get(string $key, $default = null)
    {
        $all = Cache::rememberForever('settings.all', fn () => static::pluck('value', 'key')->all());

        return $all[$key] ?? self::DEFAULTS[$key] ?? $default;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }
}
