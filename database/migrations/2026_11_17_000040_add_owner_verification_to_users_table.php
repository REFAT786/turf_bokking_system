<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Owners only: pending | approved | rejected (null for other roles)
            $table->string('verification_status', 20)->nullable()->index()->after('business_name');
            $table->string('nid_number', 30)->nullable()->after('verification_status');
            $table->string('nid_front_path')->nullable()->after('nid_number');
            $table->string('nid_back_path')->nullable()->after('nid_front_path');
            $table->string('business_document_path')->nullable()->after('nid_back_path');
            $table->text('verification_note')->nullable()->after('business_document_path');
            $table->timestamp('verification_submitted_at')->nullable()->after('verification_note');
            $table->timestamp('verified_at')->nullable()->after('verification_submitted_at');
            $table->foreignId('verified_by')->nullable()->after('verified_at');
        });

        // Owners that existed before verification was introduced keep working.
        DB::table('users')->where('role', 'owner')->update(['verification_status' => 'approved', 'verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_status', 'nid_number', 'nid_front_path', 'nid_back_path', 'business_document_path',
                'verification_note', 'verification_submitted_at', 'verified_at', 'verified_by',
            ]);
        });
    }
};
