<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('broadcast_messages', function (Blueprint $table) {
            // This is the missing column causing the error
            $table->boolean('is_active')->default(false)->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcast_messages', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
