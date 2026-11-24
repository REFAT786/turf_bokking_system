<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerBookingController;
use App\Http\Controllers\Panel;
use App\Http\Controllers\TurfBrowseController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', [TurfBrowseController::class, 'home'])->name('home');
Route::get('/turfs', [TurfBrowseController::class, 'index'])->name('turfs.index');
Route::get('/turfs/{turf}', [TurfBrowseController::class, 'show'])->name('turfs.show');
Route::view('/how-it-works', 'how')->name('how');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/register/owner', [AuthController::class, 'showRegister'])->name('register.owner');
    Route::post('/register/owner', [AuthController::class, 'register'])->name('register.owner.store');
    Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// My profile (every logged-in user)
Route::middleware('auth')->controller(\App\Http\Controllers\ProfileController::class)->group(function () {
    Route::get('/profile', 'show')->name('profile');
    Route::put('/profile', 'update')->name('profile.update');
    Route::put('/profile/password', 'password')->middleware('throttle:6,1')->name('profile.password');
});

// Customer
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::post('/turfs/{turf}/book', [CustomerBookingController::class, 'store'])->middleware('throttle:10,1')->name('turfs.book');
    Route::get('/my/bookings', [CustomerBookingController::class, 'index'])->name('my.bookings');
    Route::get('/my/bookings/{booking}', [CustomerBookingController::class, 'show'])->name('my.bookings.show');
    Route::post('/my/bookings/{booking}/payment', [CustomerBookingController::class, 'submitPayment'])->name('my.bookings.payment');
    Route::post('/my/bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])->name('my.bookings.cancel');
    Route::post('/my/bookings/{booking}/review', [CustomerBookingController::class, 'review'])->name('my.bookings.review');
});

// Turf owner + staff panel
Route::middleware(['auth', 'role:owner,staff'])->prefix('panel')->name('panel.')->group(function () {
    Route::get('/', Panel\DashboardController::class)->name('dashboard');
    Route::get('/schedule', [Panel\BookingController::class, 'schedule'])->name('schedule');
    Route::get('/bookings', [Panel\BookingController::class, 'index'])->name('bookings.index');
    Route::post('/bookings', [Panel\BookingController::class, 'storeWalkIn'])->name('bookings.store');
    Route::post('/bookings/{booking}/confirm', [Panel\BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('/bookings/{booking}/reject', [Panel\BookingController::class, 'rejectPayment'])->name('bookings.reject');
    Route::post('/bookings/{booking}/complete', [Panel\BookingController::class, 'complete'])->name('bookings.complete');
    Route::post('/bookings/{booking}/no-show', [Panel\BookingController::class, 'noShow'])->name('bookings.noshow');
    Route::post('/bookings/{booking}/cancel', [Panel\BookingController::class, 'cancel'])->name('bookings.cancel');

    // Owner only
    Route::middleware('role:owner')->group(function () {
        Route::resource('turfs', Panel\TurfController::class)->except(['show', 'destroy']);
        Route::get('/staff', [Panel\StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [Panel\StaffController::class, 'store'])->name('staff.store');
        Route::post('/staff/{user}/toggle', [Panel\StaffController::class, 'toggle'])->name('staff.toggle');
        Route::post('/staff/{user}/turfs', [Panel\StaffController::class, 'turfs'])->name('staff.turfs');
        Route::put('/staff/{user}', [Panel\StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{user}', [Panel\StaffController::class, 'destroy'])->name('staff.destroy');
        Route::get('/reports', Panel\ReportController::class)->name('reports');
        Route::get('/verification', [Panel\VerificationController::class, 'show'])->name('verification');
        Route::post('/verification', [Panel\VerificationController::class, 'store'])->name('verification.store');
    });
});

// Super admin
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->controller(AdminController::class)->group(function () {
    Route::get('/', 'dashboard')->name('dashboard');
    Route::get('/turfs', 'turfs')->name('turfs');
    Route::post('/turfs/{turf}/status', 'turfStatus')->name('turfs.status');
    Route::get('/users', 'users')->name('users');
    Route::get('/owners', 'owners')->name('owners');
    Route::get('/owners/{user}/documents/{document}', 'ownerDocument')->name('owners.document');
    Route::post('/owners/{user}/approve', 'approveOwner')->name('owners.approve');
    Route::post('/owners/{user}/reject', 'rejectOwner')->name('owners.reject');
    Route::post('/users/{user}/toggle', 'toggleUser')->name('users.toggle');
    Route::post('/users/{user}/password', 'setUserPassword')->name('users.password');
    Route::get('/bookings', 'bookings')->name('bookings');
    Route::get('/settings', 'settings')->name('settings');
    Route::get('/content', [\App\Http\Controllers\Admin\ContentController::class, 'edit'])->name('content');
    Route::post('/content', [\App\Http\Controllers\Admin\ContentController::class, 'update'])->name('content.save');
    Route::post('/content/{section}/reset', [\App\Http\Controllers\Admin\ContentController::class, 'reset'])->name('content.reset');
    Route::post('/settings', 'saveSettings')->name('settings.save');
});
