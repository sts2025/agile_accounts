<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-tenant policy: what percentage of net surplus must be transferred
     * to the Statutory Reserve Fund before dividends are considered fully
     * distributable. One row per tenant, same pattern as
     * loan_penalty_settings.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sacco_reserve_settings')) {
            Schema::create('sacco_reserve_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->unique()->constrained('loan_managers')->onDelete('cascade');
                $table->decimal('statutory_reserve_percent', 5, 2)->default(20.00);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sacco_reserve_settings');
    }
};
