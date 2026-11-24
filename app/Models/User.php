<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_OWNER = 'owner';
    public const ROLE_STAFF = 'staff';
    public const ROLE_CUSTOMER = 'customer';

    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_APPROVED = 'approved';
    public const VERIFICATION_REJECTED = 'rejected';

    /** Owner verification documents: key => [column, label]. */
    public const VERIFICATION_DOCUMENTS = [
        'nid_front' => ['nid_front_path', 'NID — front'],
        'nid_back' => ['nid_back_path', 'NID — back'],
        'selfie' => ['selfie_path', 'Selfie with NID'],
        'business' => ['business_document_path', 'Trade license / proof of business'],
    ];

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'owner_id', 'business_name', 'is_active',
        'verification_status', 'nid_number', 'nid_front_path', 'nid_back_path', 'selfie_path', 'business_document_path',
        'verification_note', 'verification_submitted_at', 'verified_at', 'verified_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** Mirror the DB default so freshly created (not yet reloaded) models are active. */
    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'verification_submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Every account must have BOTH a mobile number (login) and an email (password reset).
     * One set of rules used by sign-up, owner sign-up, staff create/edit and My profile.
     */
    public static function contactRules(?int $ignoreId = null): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/', \Illuminate\Validation\Rule::unique('users', 'phone')->ignore($ignoreId)],
            'email' => ['required', 'string', 'max:150', 'email:filter', 'regex:/@[^@\s]+\.[a-z]{2,}$/i', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($ignoreId)],
        ];
    }

    public static function contactMessages(): array
    {
        return [
            'phone.required' => 'Mobile number is required — it is used to log in.',
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'phone.unique' => 'This mobile number is already registered. Try logging in or use “Forgot password?”.',
            'email.required' => 'Email is required — it is used to reset your password.',
            'email.email' => 'Enter a valid email address, e.g. name@gmail.com.',
            'email.regex' => 'Enter a valid email address, e.g. name@gmail.com.',
            'email.unique' => 'This email is already registered. Try logging in or use “Forgot password?”.',
        ];
    }

    /** Store emails trimmed + lowercase so “Rahim@Gmail.com” and “rahim@gmail.com” are the same account. */
    protected function email(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(set: fn ($v) => $v === null ? null : mb_strtolower(trim($v)));
    }

    /** Store mobile numbers in one format (01XXXXXXXXX) whatever was typed (+880, spaces, dashes). */
    protected function phone(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(set: fn ($v) => $v === null ? null : \App\Http\Controllers\AuthController::normalizePhone((string) $v));
    }

    public function isSuperAdmin(): bool { return $this->role === self::ROLE_SUPER_ADMIN; }
    public function isOwner(): bool { return $this->role === self::ROLE_OWNER; }
    public function isStaff(): bool { return $this->role === self::ROLE_STAFF; }
    public function isCustomer(): bool { return $this->role === self::ROLE_CUSTOMER; }

    /** The owner account whose turfs this user manages (owner => self, staff => employer). */
    public function businessOwnerId(): ?int
    {
        return match ($this->role) {
            self::ROLE_OWNER => $this->id,
            self::ROLE_STAFF => $this->owner_id,
            default => null,
        };
    }

    /** Query for the turfs this user may manage: an owner → all their turfs; staff → only the turfs assigned to them. */
    public function managedTurfs()
    {
        $q = Turf::where('owner_id', $this->businessOwnerId() ?? 0);

        return $this->isStaff()
            ? $q->whereIn('id', fn ($s) => $s->select('turf_id')->from('turf_staff')->where('user_id', $this->id))
            : $q;
    }

    public function canManageTurf(Turf $turf): bool
    {
        if ($this->businessOwnerId() === null || $turf->owner_id !== $this->businessOwnerId()) {
            return false;
        }

        return ! $this->isStaff() || $this->assignedTurfs()->whereKey($turf->id)->exists();
    }

    /** Staff only: the turfs they work at. */
    public function assignedTurfs(): BelongsToMany
    {
        return $this->belongsToMany(Turf::class, 'turf_staff')->withTimestamps();
    }

    /** The owner account that must be verified for this user's business (owner => self, staff => employer). */
    public function businessOwner(): ?User
    {
        return match ($this->role) {
            self::ROLE_OWNER => $this,
            self::ROLE_STAFF => $this->employer,
            default => null,
        };
    }

    public function isVerifiedOwner(): bool
    {
        return $this->isOwner() && $this->verification_status === self::VERIFICATION_APPROVED;
    }

    public function verificationPending(): bool { return $this->verification_status === self::VERIFICATION_PENDING; }
    public function verificationRejected(): bool { return $this->verification_status === self::VERIFICATION_REJECTED; }

    /** Is a stored verification document an image (vs. a PDF)? */
    public function documentIsImage(string $key): bool
    {
        $path = $this->{self::VERIFICATION_DOCUMENTS[$key][0] ?? ''} ?? '';

        return (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', $path);
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => route('admin.dashboard'),
            self::ROLE_OWNER, self::ROLE_STAFF => route('panel.dashboard'),
            default => route('my.bookings'),
        };
    }

    public function turfs(): HasMany { return $this->hasMany(Turf::class, 'owner_id'); }
    public function staff(): HasMany { return $this->hasMany(User::class, 'owner_id'); }
    public function employer(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function bookings(): HasMany { return $this->hasMany(Booking::class); }
}
