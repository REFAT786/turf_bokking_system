<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turfs', function (Blueprint $table) {
            $table->json('gallery')->nullable()->after('cover_image'); // extra photo paths on the public disk
        });
    }

    public function down(): void
    {
        Schema::table('turfs', function (Blueprint $table) {
            $table->dropColumn('gallery');
        });
    }
};
