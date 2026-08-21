<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Persists which loan product (Product Settings) a loan was created
     * under, so repayment-time logic (compulsory savings split, collateral
     * ratio lookups, etc.) can find it later. Previously this was only
     * used transiently at loan-creation time and never saved.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('loans', 'mfi_loan_product_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->unsignedBigInteger('mfi_loan_product_id')->nullable()->after('loan_manager_id');
            });
        }

        // No FK constraint: loan products can be deactivated/managed independently,
        // and legacy loans predating the MFI upgrade will have this as null.
    }

    public function down(): void
    {
        if (Schema::hasColumn('loans', 'mfi_loan_product_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropColumn('mfi_loan_product_id');
            });
        }
    }
};
