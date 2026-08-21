<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a disbursed loan to the journal entry that posted it (Dr Loan
     * Portfolio / Cr Cash or Bank), so Reverse Disbursement can post an
     * exact offsetting entry rather than guessing amounts/accounts.
     * Nullable because: (a) a loan may not be disbursed yet, and (b) a
     * tenant with no chart of accounts set up can still disburse loans —
     * auto-posting is skipped gracefully when there's nothing to post to.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('loans', 'disbursement_journal_entry_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->foreignId('disbursement_journal_entry_id')->nullable()->after('collateral_locked')
                    ->constrained('journal_entries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('loans', 'disbursement_journal_entry_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropForeign(['disbursement_journal_entry_id']);
                $table->dropColumn('disbursement_journal_entry_id');
            });
        }
    }
};
