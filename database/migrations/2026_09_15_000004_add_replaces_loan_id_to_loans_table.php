<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Loan top-up / restructuring: a new loan can "replace" an existing
     * disbursed loan, netting the old loan's remaining principal + interest
     * off the new loan's proceeds at disbursement time instead of requiring
     * the client to fully repay the old loan first. replaces_loan_id is
     * nullable — every ordinary loan leaves it null and behaves exactly as
     * before.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('loans', 'replaces_loan_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->foreignId('replaces_loan_id')->nullable()->after('client_group_id')
                    ->constrained('loans')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('loans', 'replaces_loan_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropForeign(['replaces_loan_id']);
                $table->dropColumn('replaces_loan_id');
            });
        }
    }
};
