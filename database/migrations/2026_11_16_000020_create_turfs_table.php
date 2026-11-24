<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('area')->index();      // e.g. Mirpur, Dhanmondi
            $table->string('city')->index();      // e.g. Dhaka
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 20);
            $table->string('size')->nullable();   // e.g. 5-a-side, 7-a-side
            $table->json('facilities')->nullable();
            $table->string('cover_image')->nullable();

            // Pricing & schedule
            $table->unsignedInteger('price_per_hour');            // BDT, day price
            $table->unsignedInteger('night_price_per_hour')->nullable(); // BDT, after night_starts_at
            $table->time('night_starts_at')->nullable();
            $table->time('opens_at')->default('06:00:00');
            $table->time('closes_at')->default('00:00:00');        // <= opens_at means closes next day
            $table->unsignedSmallInteger('slot_minutes')->default(60);
            $table->unsignedTinyInteger('advance_percent')->default(30); // % paid online to confirm

            // Payment receiving accounts (money goes straight to the owner)
            $table->string('bkash_number', 20)->nullable();
            $table->string('nagad_number', 20)->nullable();
            $table->string('rocket_number', 20)->nullable();
            $table->text('payment_note')->nullable();

            $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turfs');
    }
};
