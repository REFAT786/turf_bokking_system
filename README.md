# TurfBook BD — Football turf booking platform

Laravel 13 + MySQL + Tailwind + Alpine. Zero paid services.

## Run locally
```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate   # set DB_* (MySQL: turf_booking)
php artisan migrate --seed
php artisan storage:link
php artisan serve                                   # http://localhost:8000
php artisan schedule:work                           # auto-cancels unpaid bookings every minute
```

Demo logins (password `password`):

| Role | Login |
|---|---|
| Super admin | admin@turfbook.test |
| Turf owner | 01711111111 |
| Staff / manager | 01722222222 |
| Player | 01733333333 |

## Booking & money flow
1. Player picks consecutive slots → booking is **held** (`pending_payment`) with a deadline = now + *payment_deadline_hours* (super admin setting, default 4), never later than kick-off.
2. Player sends the advance (turf's advance %) by bKash/Nagad/Rocket **directly to the owner's number** and submits the TrxID → `payment_submitted`.
3. Owner/staff checks their wallet app and taps **Payment received** → `confirmed` (or **Not received** → cancelled).
4. After the game: **Played & paid** → `completed` (rest collected at venue), or **No-show**.
5. No TrxID by the deadline → auto `cancelled`, slot released.
   - `bookings:expire` runs every minute via the scheduler (cron: `* * * * * php artisan schedule:run`).
   - Without cron, a web middleware sweeps once a minute, and slot availability ignores overdue holds anyway.

## Production cron
```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```
