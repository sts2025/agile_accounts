<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * repayment_schedules has existed since 2025_10_20 but nothing writes
     * to it yet — RepaymentScheduleGenerator (see app/Services) is the
     * first real writer. Adding installment_number now, before any rows
     * exist, so "Installment 3 of 12" can be displayed without relying on
     * due_date ordering alone (two installments could share a date on a
     * very short daily loan).
     */
    public function up(): void
    {
        Schema::table('repayment_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('repayment_schedules', 'installment_number')) {
                $table->unsignedInteger('installment_number')->nullable()->after('loan_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repayment_schedules', function (Blueprint $table) {
            $table->dropColumn('installment_number');
        });
    }
};
