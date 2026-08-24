<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit snapshot of each time a manager runs loan classification
     * provisioning. The classification itself (which tier each loan is in
     * right now, today) is always computed live from current loan/payment
     * data — nothing about it is stored. This table only records what the
     * required reserve level was *at the moment a run was triggered* and
     * what adjusting journal entry (if any) was posted to bring the Loan
     * Loss Reserve account to that level, so there's a paper trail of
     * provisioning decisions over time even though the underlying
     * classification changes daily as loans age or get repaid.
     */
    public function up(): void
    {
        if (!Schema::hasTable('loan_provisioning_runs')) {
            Schema::create('loan_provisioning_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->constrained('loan_managers')->onDelete('cascade');

                $table->date('run_date');
                $table->unsignedInteger('loan_count');
                $table->decimal('total_outstanding', 15, 2);
                $table->decimal('required_reserve', 15, 2);
                $table->decimal('previous_reserve', 15, 2)->default(0);
                $table->decimal('delta', 15, 2);

                // Snapshot of per-tier counts/outstanding/provision at the
                // moment this run happened, e.g.
                // {"Normal":{"count":10,"outstanding":50000,"provision":500}, ...}
                $table->json('breakdown')->nullable();

                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_provisioning_runs');
    }
};
