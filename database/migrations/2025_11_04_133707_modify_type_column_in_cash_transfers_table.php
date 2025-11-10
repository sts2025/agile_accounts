<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- Import DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_transfers', function (Blueprint $table) {
            // This changes the column to a string
            $table->string('type', 50)->default('payable')->change();
        });

        // This fixes all your old "fix" and "N/A (fix)" data
        DB::table('cash_transfers')
            ->where('type', 'fix')
            ->orWhere('type', 'N/A (fix)')
            ->update(['type' => 'payable']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_transfers', function (Blueprint $table) {
            // This changes it back if needed
            $table->integer('type')->change();
        });
    }
};