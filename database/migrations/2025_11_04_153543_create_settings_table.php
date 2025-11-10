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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            // This stores the currency symbol (UGX or RWF)
            $table->string('currency_symbol', 10)->default('UGX');
            // This stores the support number shown on public pages
            $table->string('support_phone', 50)->nullable();
            $table->timestamps();
        });

        // Seed the table with default values
        DB::table('settings')->insert([
            'currency_symbol' => 'UGX',
            'support_phone' => '+256 700 123 456', // Default Uganda number
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
