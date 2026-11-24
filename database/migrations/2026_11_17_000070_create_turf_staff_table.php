<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which turfs each staff member works at (an owner can have several turfs).
        Schema::create('turf_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turf_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['turf_id', 'user_id']);
        });

        // Existing staff keep access to all of their owner's current turfs.
        $rows = DB::table('users')->where('users.role', 'staff')->whereNotNull('users.owner_id')
            ->join('turfs', 'turfs.owner_id', '=', 'users.owner_id')
            ->get(['users.id as user_id', 'turfs.id as turf_id'])
            ->map(fn ($r) => ['user_id' => $r->user_id, 'turf_id' => $r->turf_id, 'created_at' => now(), 'updated_at' => now()])
            ->all();
        DB::table('turf_staff')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('turf_staff');
    }
};
