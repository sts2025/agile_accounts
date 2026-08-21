<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Double-entry journal: one journal_entries header per posting, with
     * two or more journal_entry_lines underneath it (debits must equal
     * credits across the lines — enforced in JournalEntryController, not
     * at the DB level). 'source' distinguishes entries typed manually via
     * the General Journal screen from ones auto-posted by other parts of
     * the app (loan disbursement, etc.) in later work.
     */
    public function up(): void
    {
        if (!Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->constrained('loan_managers')->onDelete('cascade');
                $table->date('entry_date');
                $table->string('reference_no')->nullable();
                $table->text('narration')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                // 'manual' (typed via General Journal) or a code identifying
                // which automated flow posted it, e.g. 'loan_disbursement'.
                $table->string('source', 40)->default('manual');
                $table->boolean('is_reversed')->default(false);
                // Points from a reversal entry back at the original entry it
                // reverses, and vice versa isn't needed — is_reversed on the
                // original plus this pointer on the reversal is enough to
                // trace either direction.
                $table->foreignId('reverses_journal_entry_id')->nullable()
                    ->constrained('journal_entries')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
