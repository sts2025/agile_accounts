<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a per-loan interest calculation method. Every existing loan
     * (and the default for new ones) keeps the original flat-rate
     * calculation — principal * rate% charged once, split evenly across
     * installments — exactly as before. 'reducing_balance' is a new,
     * opt-in alternative: equal principal per installment, interest
     * charged each period only on the outstanding balance, so the
     * installment amount declines over the life of the loan.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('loans', 'interest_method')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->string('interest_method', 20)->default('flat')->after('interest_rate');
            });
        }

        if (!Schema::hasColumn('mfi_products', 'interest_method')) {
            Schema::table('mfi_products', function (Blueprint $table) {
                $table->string('interest_method', 20)->default('flat')->after('interest_rate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('loans', 'interest_method')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropColumn('interest_method');
            });
        }

        if (Schema::hasColumn('mfi_products', 'interest_method')) {
            Schema::table('mfi_products', function (Blueprint $table) {
                $table->dropColumn('interest_method');
            });
        }
    }
};
