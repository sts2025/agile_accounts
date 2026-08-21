<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a repayment to the journal entry auto-posted for it (if the
     * tenant's chart of accounts was set up at the time), so deleting the
     * payment can reverse the correct entry rather than guessing.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'journal_entry_id')) {
                $table->foreignId('journal_entry_id')->nullable()->after('loan_id')
                    ->constrained('journal_entries')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['journal_entry_id']);
            $table->dropColumn('journal_entry_id');
        });
    }
};
