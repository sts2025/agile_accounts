<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One settings row per tenant governing how the (manual, staff-applied)
     * penalty engine below defaults its amounts, plus the arrears-age
     * buckets and provisioning percentages used for loan-loss classification.
     * provision_rates is a JSON map of "days late" bucket => provision %,
     * e.g. {"30":10,"60":25,"90":50,"120":100}.
     */
    public function up(): void
    {
        if (!Schema::hasTable('loan_penalty_settings')) {
            Schema::create('loan_penalty_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->unique()->constrained('loan_managers')->onDelete('cascade');

                // 'flat' = a fixed amount per penalty; 'percent_overdue' = a
                // percentage of the loan's current outstanding balance.
                $table->string('penalty_type', 20)->default('flat');
                $table->decimal('penalty_amount', 15, 2)->default(0);
                $table->decimal('penalty_percent', 8, 2)->default(0);
                $table->unsignedInteger('grace_period_days')->default(0);

                $table->json('provision_rates')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_penalty_settings');
    }
};
