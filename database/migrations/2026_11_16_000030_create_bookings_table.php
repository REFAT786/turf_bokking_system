<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->foreignId('turf_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null for walk-in
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('total_amount');
            $table->unsignedInteger('advance_amount');
            $table->unsignedInteger('paid_amount')->default(0);

            // pending_payment -> payment_submitted -> confirmed -> completed
            //                 \-> cancelled (auto after deadline / by user / by owner)
            $table->enum('status', ['pending_payment', 'payment_submitted', 'confirmed', 'completed', 'cancelled', 'no_show'])
                ->default('pending_payment')->index();
            $table->enum('source', ['online', 'walk_in'])->default('online');

            $table->string('payment_method', 20)->nullable(); // bkash | nagad | rocket | cash
            $table->string('payer_number', 20)->nullable();
            $table->string('trx_id', 40)->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->timestamp('payment_deadline_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['turf_id', 'starts_at']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turf_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('bookings');
    }
};
