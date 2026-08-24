<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail of each time a manager transfers a slice of net surplus
     * into the Statutory Reserve Fund. Same "snapshot + journal entry
     * link" pattern as loan_provisioning_runs — the underlying P&L for a
     * period can be recomputed live at any time, but what was actually
     * decided/posted for a given period needs a permanent record.
     */
    public function up(): void
    {
        if (!Schema::hasTable('statutory_reserve_transfers')) {
            Schema::create('statutory_reserve_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->constrained('loan_managers')->onDelete('cascade');

                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('net_surplus', 15, 2);
                $table->decimal('reserve_percent', 5, 2);
                $table->decimal('reserve_amount', 15, 2);

                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_reserve_transfers');
    }
};
