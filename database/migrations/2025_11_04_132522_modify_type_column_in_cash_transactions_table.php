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
        Schema::table('cash_transactions', function (Blueprint $table) {
            // This changes the column to a string
            $table->string('type', 50)->default('payable')->change();
        });

        // This fixes all your old "fix" and "N/A (fix)" data
        DB::table('cash_transactions')
            ->where('type', 'fix')
            ->orWhere('type', 'N/A (fix)')
            ->update(['type' => 'payable']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            // This changes it back if needed, assumes it was an INT
            // Note: This might fail if you have text data,
            // but it's good practice to have the down() method.
            $table->integer('type')->change();
        });
    }
};